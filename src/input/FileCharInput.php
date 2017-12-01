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


class FileCharInput implements CharInput
{
    private $filePath;
    private $fp;
    private $line_;
    private $col;

    public function __construct(string $filePath="")
    {
        $this->filePath = $filePath;
        $this->fp = null;
        $this->line_ = 1;
        $this->col = 1;
    }

    function open()
    {
        if (!empty($this->filePath)) {
            $this->fp = fopen($this->filePath, "r");
        } else {
            $this->fp = STDIN;
        }

    }

    function close()
    {
        if ($this->fp !== null) {
            if (!empty($this->filePath)) {
                fclose($this->fp);
            }
            $this->fp = null;
        }
    }

    function hasMoreChars() : bool
    {
        if (feof($this->fp)) {
            $result = false;
        } else {
            $result = true;
        }
        return $result;
    }

    function nextChar() : string
    {
        $ch = fgetc($this->fp);
        if ($ch !== "\n") {
            $this->col += 1;
        } else {
            $this->col = 1;
            $this->line_++;
        }

        return $ch;
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