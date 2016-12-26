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
    private $caseSensitive;

    public function __construct()
    {
        $this->bufSize = 1;
        $this->wsChars = " \t\r\n";
        $this->commentTypes = [];
        $this->stringTypes = [];
        $this->symbols = [];
        $this->terminals = [];
        $this->keywords = [];
        $this->caseSensitive = true;
    }

    public function setWhitespace($wsChars)
    {
        $this->wsChars = $wsChars;
    }

    public function setCaseSensitive($caseSensitive=true)
    {
        $this->caseSensitive = $caseSensitive;
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
        $config->caseSensitive = $this->caseSensitive;

        return new TokenInputImpl($charIn, $config);
    }

}
