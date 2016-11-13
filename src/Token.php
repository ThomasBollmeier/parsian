<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/13/16
 * Time: 11:07 AM
 */

namespace tbollmeier\parsian;


class Token
{
    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return Position
     */
    public function getStartPos(): Position
    {
        return $this->startPos;
    }

    /**
     * @return Position
     */
    public function getEndPos(): Position
    {
        return $this->endPos;
    }

    private $content;
    private $type;
    private $startPos;
    private $endPos;

    public function __construct(string $content, string $type, Position $start, Position $end)
    {
        $this->content = $content;
        $this->type = $type;
        $this->startPos = $start;
        $this->endPos = $end;
    }

    public function __toString()
    {
        return "#{$this->type}: {$this->content}" .
            " @ ({$this->startPos->line}, {$this->startPos->column})";
    }

}