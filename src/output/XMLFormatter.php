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

namespace tbollmeier\parsian\output;


class XMLFormatter implements Visitor
{
    private $offset;
    private $indentSize;
    private $xml;

    public function __construct($indentSize=2)
    {
        $this->offset = 0;
        $this->indentSize = $indentSize;
        $xml = "";
    }

    public function getXml()
    {
        return $this->xml;
    }

    public function enter(Ast $ast)
    {
        $xml = "<{$ast->getName()}";

        foreach ($ast->getAttrs() as $key => $value) {
            $xml .= " $key=\"$value\"";
        }

        if (empty($ast->getText()) && empty($ast->getChildren())) {
            $xml .= " />";
        } else {
            $xml .= ">";
        }

        if (!empty($ast->getText())) {
            $xml .= $ast->getText();
        }

        if (empty($ast->getChildren())) {
            $xml .= "</{$ast->getName()}>";
        }

        $this->writeln($xml);

        $this->offset += $this->indentSize;
    }

    public function leave(Ast $ast)
    {
        $this->offset -= $this->indentSize;

        if (!empty($ast->getChildren())) {
            $this->writeln("</{$ast->getName()}>");
        }
    }

    private function writeln($text)
    {
        for ($i=0; $i<$this->offset; $i++) {
            $this->xml .= " ";
        }
        $this->xml .= $text . PHP_EOL;
    }
}