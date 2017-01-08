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

use tbollmeier\parsian\Lexer;
use tbollmeier\parsian\StringCharInput;
use tbollmeier\parsian\TokenStream;


class TokenStreamTest extends TestCase
{
    public function testStream()
    {
        $lexer = new Lexer();
        $lexer->addTerminal("/[a-z]+/", "ID");

        $code = "a b c d e f";

        $stream = new TokenStream($lexer->createTokenInput(new StringCharInput($code)));

        $stream->openTokenInput();

        $stream->newConsumption();

        $stream->consumeMany(2);

        $stream->commitConsumption();

        self::assertEquals("c", $stream->lookup()->getContent());

        $stream->rollbackConsumption();

        self::assertEquals("a", $stream->lookup()->getContent());

        $stream->closeTokenInput();


    }

}

