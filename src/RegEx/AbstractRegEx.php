<?php declare(strict_types=1);
namespace Vendi\LogParser\RegEx;

abstract class AbstractRegEx
{
    public const IP_SIMPLE = '[\d\.]+';

    public const NOT_SPACE = '[^\s]+';

    public const DIGITS_ONLY = '\d+';

    public const NOT_QUOTE = '[^"]+';

    private $_name;

    abstract public function get_final_pattern() : string;

    protected function __construct(string $name)
    {
        $this->_name = $name;
    }

    public function get_name() : string
    {
        return $this->_name;
    }
}
