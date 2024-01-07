<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RTests extends TestCase
{
    private string $_file0;
    private string $_file1;

    public function test_whitelist()
    {
        $a = 'a';
        $b = R::whitelist($a, ['a', 'b', 'c']);
        $this->assertSame($a, $b);

        $a = 100;
        $b = R::whitelist($a, [100, 200, 300]);
        $this->assertSame($a, $b);

        $default = 'default';
        $a = 400;
        $b = R::whitelist($a, ['valueA', 'valueB', 'valueC'], $default);

        $this->assertSame($default, $b);

        $default = 600;
        $a = 500;
        $b = R::whitelist($a, [100, 200, 300], $default);

        $this->assertSame($default, $b);

        $this->expectException(InvalidArgumentException::class);

        $a = 'value';
        $b = R::whitelist($a, []);
    }

    public function test_sanitize()
    {
        $value = ' a  B c @$  aBc ';
        $result = R::sanitize($value, true);

        $this->assertSame('_a__B_c_@$__aBc_', $result);

        $value = ' a  B c @$  aBc ';
        $result = R::sanitize($value, false);

        $this->assertSame('a__B_c_____aBc', $result);
    }

    public function test_nextName()
    {
        $file = __DIR__ . '/file.txt';
        $nameA = R::nextName($file);

        $this->assertSame(__DIR__ . '/file_2.txt', $nameA);
    }

    public function test_event()
    {
        R::unbind('execute');

        $a = 0;
        $calculate = function () use (&$a) { $a = 1 + 1; };
        $echo = function () { echo 'executed!'; };

        R::bind('execute', $calculate);
        R::bind('execute', $echo);
        R::call('execute');

        $this->expectOutputString('executed!');
        $this->assertSame(2, $a);
    }

    public function test_event_bis()
    {
        R::unbind('execute');

        $a = 2;
        $b = 'C';
        $calculate = function ($c) use (&$a) { $a = $c + $a; };
        $concat = function ($c) use (&$b) { $b = $b . $c; };

        R::bind('execute', $calculate);
        R::bind('execute', $concat);
        R::call('execute', 2);

        $this->assertSame(4, $a);
        $this->assertSame('C2', $b);
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_file0 = __DIR__ . '/file.txt';
        $this->_file1 = __DIR__ . '/file_1.txt';

        fopen($this->_file0, "w");
        fopen($this->_file1, "w");
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        unlink($this->_file0);
        unlink($this->_file1);

        parent::tearDown();
    }
}
