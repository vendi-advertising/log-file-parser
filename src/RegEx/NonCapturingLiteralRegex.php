<?php declare(strict_types=1);
namespace Vendi\LogParser\RegEx;

final class NonCapturingLiteralRegex extends AbstractRegEx
{
    public static function create($pattern)
    {
        return SimpleRegEx::create('', $pattern);
    }

    public function get_final_pattern() : string
    {
        throw new \Exception('The method NonCapturingLiteralRegex does not store an actual pattern');
    }
}
