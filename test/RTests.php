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

    public function test_drawer()
    {
        $drawer = R::openDrawer()
            ->add(01)
            ->add(02)
            ->add(03)
                ->open()
                    ->add(11)
                    ->add(12)
                    ->add(13)
                    ->open()
                        ->add(21)
                        ->add(22)
                        ->add(23)
                    ->close()
                    ->open()
                        ->add(31)
                        ->add(32)
                        ->add(33)
                    ->close()
                ->close()
            ->close();

        $this->assertSame([01, 02, 03, [11, 12, 13, [21, 22, 23], [31, 32, 33]]], $drawer);
    }

    public function test_flatten()
    {
        $arrayA = [1, [2, 3, 4], 5];
        $arrayB = R::flattenArray($arrayA);

        $this->assertSame([1, 2, 3, 4, 5], $arrayB);
    }

    public function test_nextName()
    {
        $file = __DIR__ . '/file.txt';
        $nameA = R::nextName($file);

        $this->assertSame(__DIR__ . '/file_2.txt', $nameA);
    }

    public function test_prefixAndSuffix()
    {
        $dataA = ['valA', 'valB', 'valC'];
        R::prefix('prefix_', $dataA);

        $this->assertSame(['prefix_valA', 'prefix_valB', 'prefix_valC'], $dataA);

        $dataB = ['valA', 'valB', 'valC'];
        R::suffix('_suffix', $dataB);

        $this->assertSame(['valA_suffix', 'valB_suffix', 'valC_suffix'], $dataB);
    }

    public function test_append()
    {
        $main = 'main';
        R::append($main, '.', 'string1', 'string2', 'string3');

        $this->assertSame('main.string1.string2.string3', $main);
    }

    public function test_concat()
    {
        $main = R::concat('@', 'string1', 'string2', ['string3', 'string4'], 111, [222, 'string5']);

        $this->assertSame('string1@string2@string3@string4@111@222@string5', $main);

        $main = R::concat('@', R::SPACE, R::EMPTY, [R::SPACE, R::EMPTY]);

        $this->assertSame(R::EMPTY, $main);
    }

    public function test_event()
    {
        R::unbind('execute');

        $a = 0;
        $calculate = function () use (&$a) {
            $a = 1 + 1;
        };
        $echo = function () {
            echo 'executed!';
        };

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
        $calculate = function ($c) use (&$a) {
            $a = $c + $a;
        };
        $concat = function ($c) use (&$b) {
            $b = $b . $c;
        };

        R::bind('execute', $calculate);
        R::bind('execute', $concat);
        R::call('execute', 2);

        $this->assertSame(4, $a);
        $this->assertSame('C2', $b);
    }

    public function test_getParameter()
    {
        $_GET['get1'] = 'getValue1';
        $_POST['post2'] = 100;

        $value1 = R::getParameter('get1');
        $value2 = R::getParameter('post2');
        $value3 = R::getParameter('get3', 200);
        $value4 = R::getParameter('get4', message: 'No param!', throwException: false);
        $value5 = R::getParameter('get5');

        $this->assertSame('getValue1', $value1);
        $this->assertSame(100, $value2);
        $this->assertSame(200, $value3);
        $this->expectOutputString('No param!');
        $this->expectException(InvalidArgumentException::class);

        unset($_GET['get1']);
        unset($_POST['get2']);
    }

    public function test_require()
    {
        $_GET['get1'] = 'getValue1';
        $_POST['post2'] = 100;

        R::require('get1');
        R::require('post2');
        R::require('get3');

        $this->expectException(InvalidArgumentException::class);

        unset($_GET['get1']);
        unset($_POST['post2']);
    }

    public function test_checkArgument()
    {
        R::checkArgument(true);
        R::checkArgument(false, message: 'OK!', throwException: false);
        R::checkArgument(false);

        $this->expectOutputString('OK!');
        $this->expectException(InvalidArgumentException::class);
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
