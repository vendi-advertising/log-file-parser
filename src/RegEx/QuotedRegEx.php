<?php declare(strict_types=1);
namespace Vendi\LogParser\RegEx;

final class QuotedRegEx extends AbstractRegEx
{
    private $_items;

    public function get_items() : array
    {
        return $this->_items;
    }

    protected function __construct(string $name, AbstractRegEx...$items)
    {
        parent::__construct($name);
        $this->_items = $items;
    }

    public static function create(string $name, string $pattern)
    {
        return new self($name, SimpleRegEx::create('', $pattern));
    }

    public static function create_grouped(AbstractRegEx...$items)
    {
        return new self('', ...$items);
    }

    public function get_final_pattern() : string
    {
        $parts = [];
        foreach ($this->get_items() as $item) {
            $parts[] = $item->get_final_pattern();
        }
        $pattern = implode(' ', $parts);
        return SimpleRegEx::create_quote_wrapped($this->get_name(), $pattern)->get_final_pattern();
    }
}
