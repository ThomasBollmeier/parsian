<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/13/16
 * Time: 11:15 AM
 */

namespace tbollmeier\parsian;


class Lexer
{

    private $bufSize;
    private $wsChars;
    private $commentTypes;
    private $stringTypes;
    private $symbols;
    private $terminals;
    private $keywords;

    public function __construct()
    {
        $this->bufSize = 1;
        $this->wsChars = " \t\r\n";
        $this->commentTypes = [];
        $this->stringTypes = [];
        $this->symbols = [];
        $this->terminals = [];
        $this->keywords = [];
    }

    public function setWhitespace($wsChars)
    {
        $this->wsChars = $wsChars;
    }

    public function addCommentType(string $startSeq, string $endSeq)
    {
        $this->commentTypes[] = [$startSeq, $endSeq];
        $this->adjustBufSize($startSeq);
        $this->adjustBufSize($endSeq);
    }

    public function addStringType(string $delimSeq, string $escSeq=null)
    {
        $this->stringTypes[] = [$delimSeq, $escSeq];
        $this->adjustBufSize($delimSeq);
        $this->adjustBufSize($escSeq);
    }

    public function addSymbol(string $seq, string $name="symbol")
    {
        $this->symbols[] = [$seq, $name];
        $this->adjustBufSize($seq);
    }

    public function addKeyword(string $seq)
    {
        $this->keywords[] = $seq;
    }

    public function addTerminal(string $pattern, string $name)
    {
        $this->terminals[] = [$pattern, $name];
    }

    private function adjustBufSize(string $seq)
    {
        $size = strlen($seq);
        if ($size > $this->bufSize) {
            $this->bufSize = $size;
        }
    }

    public function createTokenInput(CharInput $charIn) : TokenInput
    {
        $config = new \stdClass();
        $config->bufSize = $this->bufSize;
        $config->wsChars = $this->wsChars;
        $config->commentTypes = $this->commentTypes;
        $config->stringTypes = $this->stringTypes;
        $config->symbols = $this->symbols;
        $config->terminals = $this->terminals;
        $config->keywords = $this->keywords;

        return new TokenInputImpl($charIn, $config);
    }

}
