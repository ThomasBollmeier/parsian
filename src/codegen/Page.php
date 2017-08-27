<?php
/*
Copyright 2017 Thomas Bollmeier <entwickler@tbollmeier.de>

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

namespace tbollmeier\parsian\codegen;


class Page
{
    private $output;
    private $indentLevel;
    private $indentSize;

    public function __construct(Output $output)
    {
        $this->output = $output;
        $this->indentLevel = 0;
        $this->indentSize = 4;
    }

    public function writeln($text="")
    {
        if (!empty($text)) {
            $padding = "";
            for ($i=0; $i<$this->indentLevel; $i++) {
                $padding .= " ";
            }
            $this->output->writeln($padding . $text);
        } else {
            $this->output->writeln($text);
        }

    }

    public function indent()
    {
        $this->indentLevel += $this->indentSize;
    }

    public function dedent()
    {
        $this->indentLevel -= $this->indentSize;
    }

}