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

namespace tbollmeier\parsian;


class TokenStream
{
    private $tokenIn;
    private $tokens;
    private $bufSize;
    private $consumedTokens;

    public function __construct(TokenInput $tokenIn)
    {
        $this->tokenIn = $tokenIn;
        $this->tokens = [];
        $this->bufSize = 1;
        $this->consumedTokens = [[]];
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
                if (!$this->matches($actualToken, $expectedTokenType)) {
                    return false;        
                }
            }

            return $actualTokens;

        } else {
            return false;
        }

    }

    private function matches(Token $actToken, $expType)
    {
        if (!is_array($expType)) {
            return $actToken->getType() == $expType; 
        } else {
            return array_search($actToken->getType(), $expType) !== false;
        }
    }

    public function consumeExpected(...$expectedTokenTypes) 
    {
        $actualTokens = call_user_func_array([$this, "checkFor"], $expectedTokenTypes);
        if ($actualTokens !== false) {
            $cnt = count($actualTokens);
            for ($i=0; $i<$cnt; $i++) {
                $this->recordConsumption(array_shift($this->tokens));
            }
            return $actualTokens;
        } else {
            throw new \Exception("Parsing error");
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
            $token = array_shift($this->tokens);
            $this->recordConsumption($token);
            return $token;
        } else {
            return false;
        }
    }

    public function consumeMany($n)
    {
        $consumed = $this->lookupMany($n);
        $cnt = count($consumed);

        for ($i=0; $i<$cnt; $i++) {
            $this->recordConsumption(array_shift($this->tokens));
        }

        return $consumed;
    }

    private function fillBuffer()
    {
        while ($this->tokenIn->hasMoreTokens() && count($this->tokens) < $this->bufSize) {
            $this->tokens[] = $this->tokenIn->nextToken();
        }
    }

    public function newConsumption()
    {
        array_unshift($this->consumedTokens, []);
    }

    public function commitConsumption()
    {
        $tokens = array_shift($this->consumedTokens);
        if (count($this->consumedTokens) > 0) {
            $this->consumedTokens[0] = array_merge($this->consumedTokens[0], $tokens);
        } else {
            $this->consumedTokens = [[]];
        }
    }

    public function rollbackConsumption()
    {
        $tokens = array_reverse(array_shift($this->consumedTokens));
        foreach ($tokens as $token) {
            array_unshift($this->tokens, $token);
        }
    }

    private function recordConsumption($token)
    {
        array_push($this->consumedTokens[0], $token);
    }

}