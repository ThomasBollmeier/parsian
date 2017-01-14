<?php
/*
Copyright 2017 Thomas Bollmeier <entwickler@tbollmeier.de>

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

use tbollmeier\parsian\input as in;
use tbollmeier\parsian\output as out;
use tbollmeier\parsian\grammar\Grammar;


class Parser
{
    private $lexer;
    private $grammar;
    private $lastErrorToken;

    public function __construct()
    {
        $this->lexer = new in\Lexer();
        $this->grammar = new Grammar();
        $this->lastErrorToken = null;
    }

    /**
     * @return in\Lexer
     */
    public function getLexer(): in\Lexer
    {
        return $this->lexer;
    }

    /**
     * @return Grammar
     */
    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    /**
     * Parse code string
     * @param string $code
     * @return Ast instance or false in case of error
     */
    public function parseString(string $code)
    {
        return $this->parse(new in\StringCharInput($code));
    }

    /**
     * Parse code from file
     * @param string $filePath
     * @return Ast instance or false in case of error
     */
    public function parseFile(string $filePath)
    {
        return $this->parse(new in\FileCharInput($filePath));
    }

    /**
     * Parse code from standard input
     * @return Ast instance or false in case of error
     */
    public function parseStdin()
    {
        return $this->parse(new in\FileCharInput(""));
    }

    public function error() : string
    {
        $token = $this->lastErrorToken;
        if ($token !== null) {
            $pos = $token->getStartPos();
            $message = "Unexpected token '{$token->getContent()}' @ ";
            $message .= "line {$pos->line}, column {$pos->column}";
            return $message;
        } else {
            return "";
        }

    }

    private function parse(in\CharInput $charIn)
    {
        $root = $this->grammar->getRoot();

        $tokenIn = $this->lexer->createTokenInput($charIn);
        $stream = new in\TokenStream($tokenIn);

        $stream->openTokenInput();

        $this->lastErrorToken = null;

        $astNodes = $root->translate($stream);

        $incomplete = $stream->hasMoreTokens();
        if ($incomplete) {
            $this->lastErrorToken = $stream->getLastUnconsumedToken();
        }

        $stream->closeTokenInput();

        return $astNodes !== false && !$incomplete ? $astNodes[0] : false;
    }

}