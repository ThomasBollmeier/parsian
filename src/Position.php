<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/13/16
 * Time: 11:04 AM
 */

namespace tbollmeier\parsian;


class Position
{
    public $line;
    public $column;

    public function __construct(int $line, int $column)
    {
        $this->line = $line;
        $this->column = $column;
    }
}