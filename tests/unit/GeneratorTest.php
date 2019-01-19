<?php

use Util\Generator;

class GeneratorTest extends \Codeception\Test\Unit
{
    public function testNumbersWithPrefix()
    {
        $this->test(null, '|TEST');
    }

    public function testNumbersWithSuffix()
    {
        $this->test('|TEST');
    }

    public function testCommonNumbers()
    {
        $this->test();
    }

    public function testDates()
    {
        $this->test('/YYYY');
        $this->test('/yy');

        $this->test(null, 'YYYY-');
        $this->test(null, 'yy-');
    }

    protected function test($prefix = null, $suffix = null)
    {
        $date = date('Y-m-d H:i:s');
        $info = Generator::dateToPattern($date);

        // Individuazione valori relativi a suffisso e prefisso
        $prefix_value = Generator::complete($prefix, $info);
        $suffix_value = Generator::complete($suffix, $info);

        $step = 3;

        // Pattern di base con numero di caratteri incrementale
        $pattern = $prefix.'#'.$suffix;

        $previous = null;
        for ($i = 0; $i < 10000; $i = $i + $step) {
            $value = $prefix_value.$this->pad($i + 1, $length).$suffix_value;
            $this->assertEquals($value, Generator::generate($pattern, $previous, $step, $info));

            $previous = $value;
        }

        // Pattern con padding
        $length = 5;
        $pattern = $prefix.str_repeat('#', $length).$suffix;

        $previous = null;
        for ($i = 0; $i < 10000; $i = $i + $step) {
            $value = $prefix_value.$this->pad($i + 1, $length).$suffix_value;
            $this->assertEquals($value, Generator::generate($pattern, $previous, $step, $info));

            $previous = $value;
        }
    }

    protected function pad($number, $length)
    {
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }
}
