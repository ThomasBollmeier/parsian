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
CODE;

        $tin = $lx->createTokenInput(new StringCharInput($code));
        $tin->open();
        $tokens = [];

        while ($tin->hasMoreTokens()) {
            $token = $tin->nextToken();
            $tokens[] = $token;
            //printf("%s".PHP_EOL, $token);
        }

        $tin->close();

        $this->assertEquals(5, count($tokens));
        $this->assertEquals("PAR_OPEN", $tokens[0]->getType());
        $this->assertEquals("key_define", $tokens[1]->getType());
        $this->assertEquals("ID", $tokens[2]->getType());
        $this->assertEquals("NUM", $tokens[3]->getType());
        $this->assertEquals("PAR_CLOSE", $tokens[4]->getType());

    }

}

