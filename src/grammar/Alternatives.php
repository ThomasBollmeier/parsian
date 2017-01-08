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


use tbollmeier\parsian\TokenStream;

class Alternatives implements Translator
{
    private $choices;

    public function __construct()
    {
        $this->choices = [];
    }

    public function add(Translator $choice)
    {
        $this->choices[] = $choice;
        return $this;
    }

    public function translate(TokenStream $stream)
    {
        $stream->newConsumption();

        foreach ($this->choices as $choice) {
            $elemNodes = $choice->translate($stream);
            if ($elemNodes !== false) {
                $stream->commitConsumption();
                return $elemNodes;
            }
        }

        $stream->rollbackConsumption();

        return false;
    }
}