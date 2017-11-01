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

namespace tbollmeier\parsian\grammar;


use tbollmeier\parsian\input\TokenStream;

class RuleRef implements Translator
{
    private $grammar;
    private $name;
    private $id;

    public function __construct(Grammar $grammar, $name, $id="")
    {
        $this->grammar = $grammar;
        $this->name = $name;
        $this->id = $id;
    }

    public function translate(TokenStream $stream)
    {
        $rule = $this->grammar->getRule($this->name);
        $astNodes = $rule->translate($stream);
        
        if ($astNodes !== false && !empty($this->id)) {
            $astNodes[0]->setAttr('id', $this->id);
        }

        return $astNodes;
    }
}