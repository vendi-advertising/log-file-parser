<?php declare(strict_types=1);
namespace Vendi\LogParser\RegEx;

final class SimpleRegEx extends AbstractRegEx
{
    private $_pattern;
    private $_wrap_left;
    private $_wrap_right;

    protected function __construct(string $name, string $pattern, string $wrap_left = '', string $wrap_right = '')
    {
        parent::__construct($name);
        $this->_pattern = $pattern;
        $this->_wrap_left = $wrap_left;
        $this->_wrap_right = $wrap_right;
    }

    public static function create(string $name, string $pattern)
    {
        return new self($name, $pattern);
    }

    public static function create_bracket_wrapped(string $name, string $pattern)
    {
        return new self($name, $pattern, '[', ']');
    }

    public static function create_quote_wrapped(string $name, string $pattern)
    {
        return new self($name, $pattern, '"', '"');
    }

    public function get_pattern() : string
    {
        return $this->_pattern;
    }

    public function get_wrap_left() : string
    {
        return $this->_wrap_left;
    }

    public function get_wrap_right() : string
    {
        return $this->_wrap_right;
    }

    public function get_final_pattern() : string
    {
        $name = $this->get_name();
        $pattern = $this->get_pattern();
        $wrap_left = $this->get_wrap_left();
        $wrap_right = $this->get_wrap_right();

        $ret = preg_quote($wrap_left, '/');
        if ($name) {
            $ret .= "(?<$name>";
        }
        $ret .= $pattern;
        if ($name) {
            $ret .= ')';
        }
        $ret .= preg_quote($wrap_right, '/');
        return $ret;
    }
}
