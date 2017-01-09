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

class Rule implements Translator
{
    private static $rules = [];

    private $name;
    private $content;
    private $customAstFn;

    public static function get($name) : Rule
    {
        return self::$rules[$name];
    }

    public function __construct(string $name, Translator $content)
    {
        $this->name = $name;
        $this->content = $content;
        $this->customAstFn = null;

        self::$rules[$name] = $this;
    }

    /**
     * Define a function to transform the ASt node into
     * a custom one. The signature is:
     *
     * function myAst(Ast $ast) : Ast
     *
     */
    public function setCustomAstFn($callback)
    {
        $this->customAstFn = $callback;
    }

    public function translate(TokenStream $stream)
    {
        $ast = new Ast($this->name);

        $contentNodes = $this->content->translate($stream);
        if ($contentNodes !== false) {
            foreach ($contentNodes as $contentNode) {
                $ast->addChild($contentNode);
            }
            if ($this->customAstFn !== null) {
                $ast = ($this->customAstFn)($ast);
            }
            return [$ast];
        } else {
            return false;
        }

    }
}