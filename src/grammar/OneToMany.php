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

class OneToMany implements Translator
{
    private $element;

    public function __construct(Translator $element)
    {
        $this->element = $element;
    }

    public function translate(TokenStream $stream)
    {
        $allNodes = [];

        $stream->newConsumption();

        $elemNodes = $this->element->translate($stream);
        if ($elemNodes !== false) {
            $allNodes = array_merge($allNodes, $elemNodes);
        } else {
            $stream->rollbackConsumption();
            return false;
        }

        while (true) {
            $elemNodes = $this->element->translate($stream);
            if ($elemNodes !== false) {
                $allNodes = array_merge($allNodes, $elemNodes);
            } else {
                break;
            }
        }

        $stream->commitConsumption();

        return $allNodes;
    }
}