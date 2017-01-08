<?php
/*
Copyright 2016-2017 Thomas Bollmeier <entwickler@tbollmeier.de>

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
use tbollmeier\parsian\ParseException;


class ParsingTest extends TestCase
{
    public function testParseError()
    {

        $codeWithError = <<<CODE
(define 99invalid 23)
CODE;

        $stream = new TokenStream($this->createLexer()->createTokenInput(new StringCharInput($codeWithError)));
        $stream->openTokenInput();

        try {

            $stream->consumeExpected("PAR_OPEN", "DEFINE", "ID", ["NUM", "ID"], "PAR_CLOSE");
            $this->fail("Exception expected");

        } catch (ParseException $error) {

            $this->assertEquals(ParseException::UNEXPECTED_TOKEN, $error->getCode());
            print($error->getMessage());

        }

    }

    private function createLexer()
    {
        return (new Lexer())
            ->addCommentType("--", PHP_EOL)
            ->addStringType('"', '\"')
            ->addSymbol("(", "PAR_OPEN")
            ->addSymbol(")", "PAR_CLOSE")
            ->addTerminal("/[a-z][a-z0-9\\-]*/", "ID")
            ->addTerminal("/[1-9]\\d*/", "NUM")
            ->addKeyword("define");

    }

}
