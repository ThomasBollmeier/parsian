<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/13/16
 * Time: 11:40 AM
 */

namespace tbollmeier\parsian;


class CharInfo
{
    public $ch;
    public $pos;

    public function __construct(string $ch, Position $pos)
    {
        $this->ch = $ch;
        $this->pos = $pos;
    }
}