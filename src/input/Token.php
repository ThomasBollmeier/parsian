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


class Token
{
    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    public function getTypes()
    {
        return array_keys($this->types);
    }

    public function matchesType($type)
    {
        return array_key_exists($type, $this->types);
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

    public function addType($type)
    {
        $this->types[$type] = true;
    }

    private $content;
    private $types;
    private $startPos;
    private $endPos;

    public function __construct(string $content, $types, Position $start, Position $end)
    {
        $this->content = $content;
        $this->types = [];
        foreach ($types as $type) {
            $this->types[$type] = true;
        }
        $this->startPos = $start;
        $this->endPos = $end;
    }

    public function __toString()
    {
        $typesStr = "[" . implode(", ", array_keys($this->types)) . "]";
        return "#{$typesStr}: {$this->content}" .
            " @ ({$this->startPos->line}, {$this->startPos->column})";
    }

}