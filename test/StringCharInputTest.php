<?php

use PHPUnit\Framework\TestCase;

use tbollmeier\parsian\StringCharInput;

class StringCharInputTest extends TestCase
{
    public function testCharInput()
    {
        $input = new StringCharInput("Just\ntesting");

        $input->open();

        for ($i=0; $i<5; $i++) {
            $char = $input->nextChar();
            echo $char;
        }
        $this->assertEquals(2, $input->line());
        $this->assertEquals(1, $input->column());
        $this->assertEquals("t", $input->nextChar());

        $input->close();
    }

}