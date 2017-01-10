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


use tbollmeier\parsian\output\Ast;
use tbollmeier\parsian\input\TokenStream;

class Terminal implements Translator
{
    private $grammar;
    private $tokenType;
    private $id;

    public function __construct(Grammar $grammar, $tokenType, $id="")
    {
        $this->grammar = $grammar;
        $this->tokenType = $tokenType;
        $this->id = $id;
    }

    public function translate(TokenStream $stream)
    {
        $token = $stream->lookup();
        if ($token && $token->getType() === $this->tokenType) {

            $stream->consume();

            $ast = new Ast('terminal', $token->getContent());
            $ast->setAttr('type', $token->getType());

            $callback = $this->grammar->customTermAst($this->tokenType);
            if ($callback !== null) {
                $ast = $callback($ast);
            }

            if (!empty($this->id)) {
                $ast->setAttr('id', $this->id);
            }

            $this->grammar->setLastTokenError(null);

            return [$ast];

        } else {

            $this->grammar->setLastTokenError($token);

            return false;

        }
    }
}