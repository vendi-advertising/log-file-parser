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

    public $utm_source;

    public $utm_medium;

    public $utm_campaign;

    public $utm_content;

    public $gclid;

    private static $regex_normal;

    private static $regex_with_connecting_ip;

    private static function create_regex_from_array(array $sub_patterns) : string
    {
        $parts = [];
        foreach ($sub_patterns as $value) {
            $parts[] = $value->get_final_pattern();
        }
        return '/^'. implode(' ', $parts) . '$/S';
    }

    public static function get_regex_normal() : string
    {
        if(!self::$regex_normal){
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
            ];
            self::$regex_normal = self::create_regex_from_array($sub_patterns);
        }

        return self::$regex_normal;
    }

    public static function get_regex_with_connecting_ip() : string
    {
        if(!self::$regex_with_connecting_ip){
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
            self::$regex_with_connecting_ip = self::create_regex_from_array($sub_patterns);
        }

        return self::$regex_with_connecting_ip;
    }

    public static function get_all_regex_patterns() : array
    {
        return [
            self::get_regex_normal(),
            self::get_regex_with_connecting_ip(),
        ];
    }


    public static function from_string(string $data) : ?array
    {
        $data = trim($data);

        //Declare these static so that we only init them once
        static $pattern;
        static $vars;

        if(!$pattern){
            $patterns = self::get_all_regex_patterns();
            foreach($patterns as $test){
                if(preg_match($test, $data, $matches)){
                    $pattern = $test;
                    break;
                }
            }
        }

        if(!$pattern){
            throw new \Exception('Could not determine pattern for line');
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

        $ret = [];
        foreach ($vars as $var) {

            if(!array_key_exists($var, $matches)){
                continue;
            }

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
