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

namespace tbollmeier\parsian\input;


class TokenInputImpl implements TokenInput
{
    private $charIn;
    private $charInfoBuf;
    private $tokens;
    private $endSeq;
    private $escSeq;

    private $bufSize;
    private $wsChars;
    private $commentTypes;
    private $stringTypes;
    private $symbols;
    private $terminals;
    private $keywords;
    private $caseSensitive;

    const MODE_NORMAL = 1;
    const MODE_COMMENT = 2;
    const MODE_STRING = 3;

    public function __construct(CharInput $charIn, $config)
    {
        $this->charIn = $charIn;
        $this->charInfoBuf = [];
        $this->tokens = [];
        $this->endSeq = null;
        $this->escSeq = null;

        $this->bufSize = $config->bufSize;
        $this->wsChars = $config->wsChars;
        $this->commentTypes = $config->commentTypes;
        $this->stringTypes = $config->stringTypes;
        $this->symbols = $config->symbols;
        $this->terminals = $config->terminals;
        $this->keywords = $config->keywords;
        $this->caseSensitive = $config->caseSensitive;

    }

    public function open()
    {
        $this->charIn->open();
    }

    public function close()
    {
        $this->charIn->close();
    }

    public function hasMoreTokens() : bool
    {
        if (empty($this->tokens)) {
            $this->findNextToken();
        }

        return !empty($this->tokens);
    }

    public function nextToken() : Token
    {
        if (empty($this->tokens)) {
            $this->findNextToken();
        }

        if (!empty($this->tokens)) {
            return array_shift($this->tokens);
        } else {
            return null;
        }
    }

    private function findNextToken()
    {
        $charInfos = [];
        $mode = self::MODE_NORMAL;

        $this->consumeWS();

        $done = false;

        while (!$done) {

            $this->fillBuffer();
            if (empty($this->charInfoBuf)) {
                break;
            }

            $content = $this->bufString();

            if (empty($content)) {
                break;
            }

            switch ($mode) {

                case self::MODE_NORMAL:

                    $checkResult = $this->checkForComment($content);

                    if ($checkResult !== false) {

                        $this->addToTokens($charInfos);

                        $mode = self::MODE_COMMENT;
                        list($startSeq, $this->endSeq) = $checkResult;
                        $this->escSeq = null;
                        $this->consume(strlen($startSeq));

                    } else {

                        $checkResult = $this->checkForString($content);

                        if ($checkResult !== false) {

                            $this->addToTokens($charInfos);

                            $mode = self::MODE_STRING;
                            list($this->endSeq, $this->escSeq) = $checkResult;
                            $charInfos = $this->consume(strlen($this->endSeq));

                        } else if ($this->isWSChar($content[0])) {

                            $this->consume(1);
                            if (!empty($charInfos)) {
                                // Whitespace found => stop the token search for now
                                $this->addToTokens($charInfos);
                                $done = true;
                            }

                        } else {

                            $charInfos = array_merge($charInfos, $this->consume(1));

                        }
                    }
                    break;

                case self::MODE_COMMENT:

                    if ($this->startsWith($content, $this->endSeq)) {

                        $this->consume(strlen($this->endSeq));
                        $mode = self::MODE_NORMAL;
                        $this->endSeq = null;

                    } else {
                        $this->consume(1);
                    }
                    break;

                case self::MODE_STRING:

                    if ($this->startsWith($content, $this->endSeq)) {

                        $charInfos = array_merge($charInfos, $this->consume(strlen($this->endSeq)));
                        $this->tokens[] = $this->createToken($charInfos, "STRING");
                        $charInfos = [];
                        $mode = self::MODE_NORMAL;
                        $this->endSeq = null;
                        $this->escSeq = null;
                        $done = true;

                    } elseif ($this->escSeq !== null && $this->startsWith($content, $this->escSeq)) {

                        $charInfos = array_merge($charInfos, $this->consume(strlen($this->escSeq)));

                    } else {

                        $charInfos = array_merge($charInfos, $this->consume(1));

                    }
                    break;
            }

        }

        // Handle trailing characters
        if (empty($this->charInfoBuf) && !empty($charInfos)) {
            $this->tokens = array_merge($this->tokens, $this->createTokens($charInfos));
        }

    }

    private function addToTokens(&$charInfos)
    {
        if (!empty($charInfos)) {
            $this->tokens = array_merge($this->tokens,
                $this->createTokens($charInfos));
            $charInfos = [];
        }
    }

    private function createTokens($charInfos)
    {
        if (empty($charInfos)) {
            return [];
        }

        $content = $this->getContent($charInfos);
        $type = $this->determTokenType($content);

        if ($type !== null) {
            return [$this->createToken($charInfos, $type)];
        } else {
            $data = $this->findNextSymbol($content);
            if ($data !== null) {

                list($symInfo, $pos) = $data;
                list($seq, $symType) = $symInfo;
                $posAfterSym = $pos + strlen($seq);

                $tokens = [];
                $left = $this->range($charInfos, 0, $pos);
                $sym = $this->range($charInfos, $pos, $posAfterSym);
                $right = $this->range($charInfos, $posAfterSym, count($charInfos));

                if (!empty($left)) {
                    $tokens[] = $this->createTerminal($left);
                }
                $tokens[] = $this->createToken($sym, $symType);
                $tokens = array_merge($tokens, $this->createTokens($right));

                return $tokens;

            } else {
                return [$this->createToken($charInfos, "terminal")];
            }
        }

    }

    private function findNextSymbol(string $content) {

        $len = strlen($content);
        $pos = 0;

        while ($pos < $len) {
            $symInfo = $this->startsWithSymbol($content, $pos);
            if ($symInfo !== null) {
                return [$symInfo, $pos];
            }
            $pos++;
        }

        return null;

    }

    private function determTokenType(string $content)
    {
        $type = null;
        $found = false;

        foreach ($this->keywords as $kw) {

            $matched = $this->caseSensitive ?
                $kw === $content :
                strtoupper($kw) === strtoupper($content);

            if ($matched) {
                $type = strtoupper($kw);
                $found = true;
                break;
            }

        }

        if (!$found) {

            foreach ($this->terminals as $term) {
                list($pattern, $name) = $term;
                if (!preg_match($pattern, $content, $matches)) {
                    continue;
                }
                if ($matches[0] !== $content) {
                    continue;
                }
                $type = $name;
                break;
            }

        }

        return $type;

    }

    private function range($arr, int $fromIncl, int $toExcl)
    {
        $res = [];
        for ($i=$fromIncl; $i < $toExcl; $i++) {
            $res[] = $arr[$i];
        }
        return $res;
    }

    private function createTerminal($charInfos)
    {
        $content = $this->getContent($charInfos);
        $type = $this->determTokenType($content) ?? "terminal";

        return $this->createToken($charInfos, $type);
    }

    private function startsWithSymbol(string $text, int $startPos)
    {
        foreach ($this->symbols as $sym) {
            if ($this->startsWith($text, $sym[0], $startPos)) {
                return $sym;
            }
        }

        return null;
    }

    private function createToken($charInfos, $type)
    {
        if (empty($charInfos)) {
            return null;
        }

        $content = $this->getContent($charInfos);
        $first = $charInfos[0];
        $last = $charInfos[count($charInfos) - 1];

        return new Token($content, $type, $first->pos, $last->pos);

    }

    private function getContent($charInfos)
    {
        $content = "";

        foreach ($charInfos as $cinfo) {
            $content .= $cinfo->ch;
        }

        return $content;
    }

    private function checkForComment(string $content)
    {
        foreach ($this->commentTypes as $commentType) {
            list($startSeq, $endSeq) = $commentType;
            if ($this->startsWith($content, $startSeq)) {
                return [$startSeq, $endSeq];
            }
        }

        return false;
    }

    private function checkForString(string $content)
    {
        foreach ($this->stringTypes as $stringType) {
            list($delimSeq, $escSeq) = $stringType;
            if ($this->startsWith($content, $delimSeq)) {
                return [$delimSeq, $escSeq];
            }
        }

        return false;
    }

    private function startsWith(string $text, string $start, int $pos = 0)
    {
        return substr($text, $pos, strlen($start)) === $start;
    }

    private function isWSChar($ch)
    {
        return strpos($this->wsChars, $ch) !== FALSE;
    }

    private function consumeWS()
    {
        while (true) {
            $this->fillBuffer();
            $content = $this->bufString();
            if (!empty($content)) {
                if ($this->isWSChar($content[0])) {
                    $this->consume(1);
                } else {
                    break;
                }

            } else {
                break;
            }
        }
    }

    private function fillBuffer()
    {
        while ($this->charIn->hasMoreChars() && count($this->charInfoBuf) < $this->bufSize) {
            $pos = new Position($this->charIn->line(), $this->charIn->column());
            $ch = $this->charIn->nextChar();
            if ($ch !== "") {
                $this->charInfoBuf[] = new CharInfo($ch, $pos);
            }
        }
    }

    private function bufString() : string
    {
        $res = "";
        foreach ($this->charInfoBuf as $charInfo) {
            $res .= $charInfo->ch;
        }
        return $res;
    }

    private function consume(int $nChars)
    {
        $i = 0;
        $consumed = [];

        while ($i < $nChars) {
            if (empty($this->charInfoBuf)) {
                break;
            }
            $consumed[] = array_shift($this->charInfoBuf);
            $i++;
            $this->fillBuffer();
        }

        return $consumed;
    }

}
