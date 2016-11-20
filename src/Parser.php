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


class Parser
{
    private $tokenIn;
    private $tokens;
    private $bufSize;

    public function __construct(TokenInput $tokenIn)
    {
        $this->tokenIn = $tokenIn;
        $this->tokens = [];
        $this->bufSize = 1;
    }

    public function openTokenInput()
    {
        $this->tokenIn->open();
    }

    public function closeTokenInput()
    {
        $this->tokenIn->close();
    }

    public function lookup()
    {
        $tokens = $this->lookupMany(1);

        return !empty($tokens) ? $tokens[0] : false;
    }

    public function checkFor(...$expectedTokenTypes)
    {
        $numExpected = count($expectedTokenTypes);
        
        $actualTokens = $this->lookupMany($numExpected);
        $numActual = count($actualTokens);
        
        if ($numActual === $numExpected) {
            for ($i=0; $i<$numActual; $i++) {
                $actualToken = $actualTokens[$i];
                $expectedTokenType = $expectedTokenTypes[$i];
                if ($actualToken->getType() !== $expectedTokenType) {
                    return false;        
                }
            }

            return $actualTokens;

        } else {
            return false;
        }

    }

    public function consumeExpected(...$expectedTokenTypes) 
    {
        $actualTokens = call_user_func_array([$this, "expect"], $expectedTokenTypes);
        if ($actualTokens !== false) {
            $cnt = count($actualTokens);
            for ($i=0; $i<$cnt; $i++) {
                array_shift($this->tokens);
            }
            return $actualTokens;
        } else {
            return false;
        }
    }

    /** lookup next n tokens
     *
     */
    public function lookupMany($n = 1)
    {
        if ($n > $this->bufSize) {
            $this->bufSize = $n;
        }

        $this->fillBuffer();

        $tokens = [];
        $size = min($n, count($this->tokens));
        for ($i=0; $i<$size; $i++) {
            $tokens[] = $this->tokens[$i];
        }

        return $tokens;
    }

    public function consume()
    {
        if (empty($this->tokens)) {
            $this->fillBuffer();
        }

        if (!empty($this->tokens)) {
            return array_shift($this->tokens);
        } else {
            return false;
        }
    }

    public function consumeMany($n)
    {
        $consumed = $this->lookupMany($n);
        $cnt = count($consumed);

        for ($i=0; $i<$cnt; $i++) {
            array_shift($this->tokens);
        }

        return $consumed;
    }

    private function fillBuffer()
    {
        while ($this->tokenIn->hasMoreTokens() && count($this->tokens) < $this->bufSize) {
            $this->tokens[] = $this->tokenIn->nextToken();
        }
    }

}