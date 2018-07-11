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
(define a-name "Tho\"mas")
(define another-name a-name)
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

        $this->assertEquals(15, count($tokens));
        $this->checkToken("PAR_OPEN", $tokens[0]);
        $this->checkToken("DEFINE", $tokens[1]);
        $this->checkToken("ID", $tokens[2]);
        $this->checkToken("NUM", $tokens[3]);
        $this->checkToken("PAR_CLOSE", $tokens[4]);
        $this->checkToken("PAR_OPEN", $tokens[5]);
        $this->checkToken("DEFINE", $tokens[6]);
        $this->checkToken("ID", $tokens[7]);
        $this->checkToken("STRING", $tokens[8]);
        $this->assertEquals('"Tho\\"mas"', $tokens[8]->getContent());
        $this->checkToken("PAR_CLOSE", $tokens[9]);
        $this->checkToken("PAR_OPEN", $tokens[10]);
        $this->checkToken("DEFINE", $tokens[11]);
        $this->checkToken("ID", $tokens[12]);
        $this->checkToken("ID", $tokens[13]);
        $this->checkToken("PAR_CLOSE", $tokens[14]);

    }

    private function checkToken($expectedType, $token)
    {
        $this->assertTrue($token->matchesType($expectedType));
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
        $this->checkToken("PAR_OPEN", $tokens[0]);
        $this->checkToken("DEFINE", $tokens[1]);
        $this->checkToken("ID", $tokens[2]);
        $this->checkToken("NUM", $tokens[3]);
        $this->checkToken("PAR_CLOSE", $tokens[4]);
        $this->checkToken("PAR_OPEN", $tokens[5]);
        $this->checkToken("DEFINE", $tokens[6]);
        $this->checkToken("ID", $tokens[7]);
        $this->checkToken("STRING", $tokens[8]);
        $this->assertEquals('"Tho\\"mas"', $tokens[8]->getContent());
        $this->checkToken("PAR_CLOSE", $tokens[9]);

    }

    public function testCaseSensitivity()
    {
        $lexer = $this->createLexer();

        $code = <<<CODE
(DEFINE whoami "alterego")
CODE;

        $tokenInput = $lexer->createTokenInput(new StringCharInput($code));

        $tokens = $this->getTokens($tokenInput);

        $this->assertEquals(5, count($tokens));
        $this->assertTrue($tokens[1]->matchesType("terminal"));

        $lexer->setCaseSensitive(false);

        $tokenInput = $lexer->createTokenInput(new StringCharInput($code));

        $tokens = $this->getTokens($tokenInput);

        $this->assertEquals(5, count($tokens));
        $this->assertTrue($tokens[1]->matchesType("DEFINE"));

    }

    public function testNestedComments()
    {
        $code = <<<CODE
(* 
this is a (*nested*) comment
*)
(define answer 42)
CODE;

        $lexer = $this->createLexer();
        $tokenIn = $lexer->createTokenInput(new StringCharInput($code));

        $tokens = $this->getTokens($tokenIn);
        $this->assertNotEmpty($tokens);

        foreach ($tokens as $token) {
            printf("%s".PHP_EOL, $token);
        }

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
        $lx->addCommentType("(*", "*)", true);
        $lx->addStringType('"', '\"');
        $lx->addSymbol("(", "PAR_OPEN");
        $lx->addSymbol(")", "PAR_CLOSE");
        $lx->addSymbol("-", "MINUS");
        $lx->addTerminal("/[a-z][a-z0-9\\-]*/", "ID");
        $lx->addTerminal("/[1-9]\\d*/", "NUM");
        $lx->addKeyword("define");
        $lx->addKeyword("not");

        return $lx;
    }

}

