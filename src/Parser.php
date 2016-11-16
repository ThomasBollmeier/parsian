<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/15/16
 * Time: 7:24 PM
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

    /** lookup next n tokens
     *
     */
    public function lookup($n = 1)
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
        $consumed = $this->lookup($n);
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