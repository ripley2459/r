<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RTests extends TestCase
{
    /**
     * @return void
     * @see R::whitelist()
     */
    public function testWhitelist_InAllowedString()
    {
        $a = 'a';
        $b = R::whitelist($a, ['a', 'b', 'c']);
        $this->assertSame($a, $b);

        $a = 100;
        $b = R::whitelist($a, [100, 200, 300]);
        $this->assertSame($a, $b);
    }

    /**
     * @return void
     * @see R::whitelist()
     */
    public function testWhitelist_NotInAllowedWithDefault()
    {
        $default = 'default';
        $a = 400;
        $b = R::whitelist($a, ['valueA', 'valueB', 'valueC'], $default);
        $this->assertSame($default, $b);

        $default = 600;
        $a = 500;
        $b = R::whitelist($a, [100, 200, 300], $default);
        $this->assertSame($default, $b);
    }

    /**
     * @return void
     * @see R::whitelist()
     */
    public function testWhitelist_NotInAllowedNoDefault()
    {
        $this->expectException(InvalidArgumentException::class);
        $a = 'value';
        $b = R::whitelist($a, []);
    }

    /**
     * @return void
     * @see R::whitelist()
     */
    public function testSanitize_Light()
    {
        $value = ' a  B c @$  aBc ';
        $result = R::sanitize($value, true);
        $this->assertSame('_a__B_c_@$__aBc_', $result);
    }

    /**
     * @return void
     * @see R::whitelist()
     */
    public function testSanitize_Heavy()
    {
        $value = ' a  B c @$  aBc ';
        $result = R::sanitize($value, false);
        $this->assertSame('a__B_c_____aBc', $result);
    }
}
