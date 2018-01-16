<?php declare(strict_types=1);
namespace Vendi\LogParser;

use Vendi\LogParser\RegEx\AbstractRegEx;
use Vendi\LogParser\RegEx\NonCapturingLiteralRegex;
use Vendi\LogParser\RegEx\QuotedRegEx;
use Vendi\LogParser\RegEx\SimpleRegEx;

final class line_data
{
    public $domain;

    public $client_ip;

    public $remote_user;

    public $datetime;

    public $http_method;

    public $http_request;

    public $http_version;

    public $http_status_code;

    public $server_bytes_sent;

    public $http_referer;

    public $http_user_agent;

    public $tls_version;

    public $tls_cipher;

    public $connecting_ip;

    public $detected_platform;

    public $detected_browser;

    public $detected_version;

    public static function from_string(string $data) : ?array
    {
        //Declare these static so that we only init them once
        static $pattern;
        static $vars;

        if (!$pattern) {

            //These are the various patterns. Each string value will be combined
            //with a single space between. Each array value will have the sub-array
            //combined with a space and then wrapped in double-quotes.
            $sub_patterns = [
                                SimpleRegEx::create('domain', AbstractRegEx::NOT_SPACE),
                                SimpleRegEx::create('client_ip', AbstractRegEx::IP_SIMPLE),
                                NonCapturingLiteralRegex::create('\-'),
                                SimpleRegEx::create('remote_user', AbstractRegEx::NOT_SPACE),
                                SimpleRegEx::create_bracket_wrapped('datetime', '[0-9a-zA-Z\/:\- ]+'),
                                QuotedRegEx::create_grouped(
                                                    SimpleRegEx::create('http_method', AbstractRegEx::NOT_SPACE),
                                                    SimpleRegEx::create('http_request', AbstractRegEx::NOT_SPACE),
                                                    SimpleRegEx::create('http_version', 'HTTP\/[\d\.]+')
                                ),
                                SimpleRegEx::create('http_status_code', AbstractRegEx::DIGITS_ONLY),
                                SimpleRegEx::create('server_bytes_sent', AbstractRegEx::DIGITS_ONLY),
                                QuotedRegEx::create('http_referer', AbstractRegEx::NOT_QUOTE),
                                QuotedRegEx::create('http_user_agent', AbstractRegEx::NOT_QUOTE),
                                SimpleRegEx::create('tls_version', AbstractRegEx::NOT_SPACE),
                                SimpleRegEx::create('tls_cipher', AbstractRegEx::NOT_SPACE),
                                SimpleRegEx::create('connecting_ip', AbstractRegEx::IP_SIMPLE),
            ];
            $parts = [];
            foreach ($sub_patterns as $value) {
                $parts[] = $value->get_final_pattern();
            }
            $pattern = '/'. implode(' ', $parts) . '/';
        }

        //Get all of the properties on this class
        if (!$vars) {
            $ref = new \ReflectionClass(static::class);
            $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
            $vars = [];
            foreach ($props as $prop) {
                $vars[] = $prop->name;
            }
        }

        if (!preg_match($pattern, $data, $matches)) {
            return null;
        }

        //Blindly assume that each field in this class exists in the matched array.
        $ret = [];
        foreach ($vars as $var) {
            if ('datetime'===$var) {
                $ret[ $var ] = date('Y-m-d H:i:s', strtotime($matches[ $var ]));
            } elseif (0 === mb_strpos($var, 'detected_')) {
                continue;
            } else {
                $ret[ $var ] = $matches[ $var ];
            }
        }

        $detected = parse_user_agent($ret['http_user_agent']);
        $ret['detected_platform'] = $detected['platform'];
        $ret['detected_browser']  = $detected['browser'];
        $ret['detected_version']  = $detected['version'];

        return $ret;
    }
}
