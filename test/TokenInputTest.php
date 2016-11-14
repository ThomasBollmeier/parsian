<?php

use PHPUnit\Framework\TestCase;

use tbollmeier\parsian\Lexer;
use tbollmeier\parsian\StringCharInput;


class TokenInputTest extends TestCase
{
    public function testTokenInput()
    {
        $lx = new Lexer();

        $lx->addCommentType("--", PHP_EOL);
        $lx->addStringType('"', '\"');
        $lx->addSymbol("(", "PAR_OPEN");
        $lx->addSymbol(")", "PAR_CLOSE");
        $lx->addTerminal("/[a-z][a-z0-9\\-]*/", "ID");
        $lx->addTerminal("/[1-9]\\d*/", "NUM");
        $lx->addKeyword("define");

        $code = <<<CODE
-- This is a comment
(define answer 42)
(define name "Tho\"mas")
CODE;

        $tin = $lx->createTokenInput(new StringCharInput($code));
        $tin->open();
        $tokens = [];

        while ($tin->hasMoreTokens()) {
            $token = $tin->nextToken();
            $tokens[] = $token;
            printf("%s".PHP_EOL, $token);
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

}

