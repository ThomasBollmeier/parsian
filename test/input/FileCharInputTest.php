<?php
/*
Copyright 2016 Thomas Bollmeier <entwickler@tbollmeier.de>

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

use PHPUnit\Framework\TestCase;

use tbollmeier\parsian\input\FileCharInput;

class FileCharInputTest extends TestCase
{
    public function testCharInput()
    {
        $input = new FileCharInput(__DIR__ . DIRECTORY_SEPARATOR . "file_char_input_test.txt");

        $input->open();

        $ch = "";
        while (true) {
            $ch = $input->nextChar();
            if ($ch === "\n") {
                break;
            }
        }
        $this->assertEquals(2, $input->line());
        $this->assertEquals(1, $input->column());
        $this->assertEquals("t", $input->nextChar());

        $input->close();
    }

}