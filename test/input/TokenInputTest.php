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

use tbollmeier\parsian\input\Lexer;
use tbollmeier\parsian\input\StringCharInput;
use tbollmeier\parsian\input\FileCharInput;
use tbollmeier\parsian\input\TokenInput;


class TokenInputTest extends TestCase
{
    public function testTokenInput()
    {

        $code = <<<CODE
-- This is a comment
(define answer 42)
(define name "Tho\"mas")
CODE;

        $tin = $this->createLexer()->createTokenInput(new StringCharInput($code));
        $tin->open();
        $tokens = [];

        while ($tin->hasMoreTokens()) {
            $token = $tin->nextToken();
            $tokens[] = $token;
            //printf("%s".PHP_EOL, $token);
        }

        $tin->close();

        $this->assertEquals(10, count($tokens));
        $this->assertEquals("PAR_OPEN", $tokens[0]->getType());
        $this->assertEquals("DEFINE", $tokens[1]->getType());
        $this->assertEquals("ID", $tokens[2]->getType());
        $this->assertEquals("NUM", $tokens[3]->getType());
        $this->assertEquals("PAR_CLOSE", $tokens[4]->getType());
        $this->assertEquals("PAR_OPEN", $tokens[5]->getType());
        $this->assertEquals("DEFINE", $tokens[6]->getType());
        $this->assertEquals("ID", $tokens[7]->getType());
        $this->assertEquals("STRING", $tokens[8]->getType());
        $this->assertEquals('"Tho\\"mas"', $tokens[8]->getContent());
        $this->assertEquals("PAR_CLOSE", $tokens[9]->getType());

    }

    public function testTokenInputFromFile()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . "code.txt";

        $tin = $this->createLexer()->createTokenInput(new FileCharInput($filePath));
        $tin->open();
        $tokens = [];

        while ($tin->hasMoreTokens()) {
            $token = $tin->nextToken();
            $tokens[] = $token;
            //printf("%s".PHP_EOL, $token);
        }

        $tin->close();

        $this->assertEquals(10, count($tokens));
        $this->assertEquals("PAR_OPEN", $tokens[0]->getType());
        $this->assertEquals("DEFINE", $tokens[1]->getType());
        $this->assertEquals("ID", $tokens[2]->getType());
        $this->assertEquals("NUM", $tokens[3]->getType());
        $this->assertEquals("PAR_CLOSE", $tokens[4]->getType());
        $this->assertEquals("PAR_OPEN", $tokens[5]->getType());
        $this->assertEquals("DEFINE", $tokens[6]->getType());
        $this->assertEquals("ID", $tokens[7]->getType());
        $this->assertEquals("STRING", $tokens[8]->getType());
        $this->assertEquals('"Tho\\"mas"', $tokens[8]->getContent());
        $this->assertEquals("PAR_CLOSE", $tokens[9]->getType());

    }

    public function testCaseSensitivity()
    {
        $lexer = $this->createLexer();

        $code = <<<CODE
(DEFINE whoami "alterego")
CODE;

        $tokenInput = $lexer->createTokenInput(new StringCharInput($code));

        $tokens = $this->getTokens($tokenInput);
        $tokenTypes = array_map(function($token) { return $token->getType(); }, $tokens);

        $this->assertEquals(5, count($tokenTypes));
        $this->assertEquals("terminal", $tokenTypes[1]);

        $lexer->setCaseSensitive(false);

        $tokenInput = $lexer->createTokenInput(new StringCharInput($code));

        $tokens = $this->getTokens($tokenInput);
        $tokenTypes = array_map(function($token) { return $token->getType(); }, $tokens);

        $this->assertEquals(5, count($tokenTypes));
        $this->assertEquals("DEFINE", $tokenTypes[1]);

    }

    private function getTokens(TokenInput $tokenInput)
    {
        $tokens = [];
        $tokenInput->open();

        while ($tokenInput->hasMoreTokens()) {
            $tokens[] = $tokenInput->nextToken();
        }

        $tokenInput->close();

        return $tokens;
    }

    private function createLexer()
    {
        $lx = new Lexer();

        $lx->addCommentType("--", PHP_EOL);
        $lx->addStringType('"', '\"');
        $lx->addSymbol("(", "PAR_OPEN");
        $lx->addSymbol(")", "PAR_CLOSE");
        $lx->addTerminal("/[a-z][a-z0-9\\-]*/", "ID");
        $lx->addTerminal("/[1-9]\\d*/", "NUM");
        $lx->addKeyword("define");

        return $lx;
    }

}

