<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/12/16
 * Time: 4:00 PM
 */

namespace tbollmeier\parsian;


class StringCharInput implements CharInput
{

    private $text;
    private $pos;
    private $line_;
    private $col;

    public function __construct(string $text)
    {
        $this->text = $text;
        $this->pos = 0;
        $this->line_ = 1;
        $this->col = 1;
    }

    function open()
    {
    }

    function close()
    {
    }

    function hasMoreChars() : bool
    {
        return $this->pos < strlen($this->text);
    }

    function nextChar() : string
    {
        $char = $this->text[$this->pos];
        $this->pos++;
        if ($char !== PHP_EOL) {
            $this->col += 1;
        } else {
            $this->col = 1;
            $this->line_++;
        }

        return $char;
    }

    function line() : int
    {
        return $this->line_;
    }

    function column() : int
    {
        return $this->col;
    }
}