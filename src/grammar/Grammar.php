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


class Grammar
{

    private $customTerminals;
    private $rules;
    private $root;

    public function __construct()
    {
        $this->customTerminals = [];
        $this->rules = [];
        $this->root = null;
    }

    public function rule($name, Translator $content, bool $isRoot = false) : Rule
    {
        $rule = new Rule($name, $content);
        $this->rules[$name] = $rule;
        if ($isRoot) {
            $this->root = $rule;
        }

        return $rule;
    }

    public function getRule($name)
    {
        return $this->rules[$name];
    }

    public function ruleRef($name, $id="") : RuleRef
    {
        return new RuleRef($this, $name, $id);
    }

    public function alt() : Alternatives
    {
        return new Alternatives();
    }

    public function seq() : Sequence
    {
        return new Sequence();
    }

    public function opt(Translator $element) : ZeroToOne
    {
        return new ZeroToOne($element);
    }

    public function oneOrMore(Translator $element) : OneToMany
    {
        return new OneToMany($element);
    }

    public function many(Translator $element) : Many
    {
        return new Many($element);
    }


    public function term($tokenType, $id="") : Terminal
    {
        $term = new Terminal($this, $tokenType, $id);
        return $term;
    }

    public function setCustomRuleAst($ruleName, $callback)
    {
        $rule = $this->rules[$ruleName];
        $rule->setCustomAstFn($callback);
    }

    public function setCustomTermAst($tokenType, $callback)
    {
        $this->customTerminals[$tokenType] = $callback;
    }

    public function customTermAst($tokenType)
    {
        if (array_key_exists($tokenType, $this->customTerminals)) {
			return $this->customTerminals[$tokenType];
        } else {
            return null;
        }

    }

    public function getRoot() : Rule
    {
        return $this->root;
    }

}