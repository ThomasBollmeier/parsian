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