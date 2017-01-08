<?php
/*
Copyright 2016-2017 Thomas Bollmeier <entwickler@tbollmeier.de>

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

use tbollmeier\parsian as pars;


class Parser
{
    const PIPE = "PIPE";
    const PAR_OPEN = "PAR_OPEN";
    const PAR_CLOSE = "PAR_CLOSE";
    const HASH = "HASH";
    const ASTERISK = "ASTERISK";
    const PLUS = "PLUS";
    const QUESTION_MARK = "QUESTION_MARK";
    const SEMICOLON = "SEMICOLON";
    const ARROW = "ARROW";
    const AT = "AT";
    const TOKEN_ID = "TOKEN_ID";
    const ID = "ID";
    const STRING = "STRING";
    const TOKEN = "TOKEN";
    const SYMBOL = "SYMBOL";
    const GRAMMAR = "GRAMMAR";


    public function __construct()
    {
    }

    public function parseString(string $grammar)
    {
        return $this->parse(new pars\StringCharInput($grammar));
    }

    public function parseFile(string $filePath)
    {
        return $this->parse(new pars\FileCharInput($filePath));
    }

    private function parse(pars\CharInput $charInput)
    {
        $tokenInput = $this->createLexer()->createTokenInput($charInput);
        $tokenStream = new pars\TokenStream($tokenInput);
        $tokenStream->openTokenInput();

        $ast = $this->grammar($tokenStream);

        $tokenStream->closeTokenInput();

        return $ast;
    }

    private function grammar(pars\TokenStream $tokenStream)
    {
        $grammar = new pars\Ast("grammar");

        while (true) {

            $token = $this->tokenOrSymbol($tokenStream);
            if ( $token !== false) {
                $grammar->addChild($token);
                continue;
            }

            $rule = $this->rule($tokenStream);
            if ( $rule !== false) {
                $grammar->addChild($rule);
                continue;
            }

            break;
        }

        return $grammar;
    }

    private function tokenOrSymbol(pars\TokenStream $tokenStream)
    {
        if ($tokenStream->checkFor(
            [self::TOKEN, self::SYMBOL],
            self::TOKEN_ID,
            self::STRING,
            self::SEMICOLON)) {

            $tokens = $tokenStream->consumeMany(4);
            $isToken = $tokens[0]->getType() == self::TOKEN;

            $tokenAst =  $isToken ?
                new pars\Ast("token") :
                new pars\Ast("symbol");

            $tokenAst->setAttr("name", $tokens[1]->getContent());
            $pattern = trim($tokens[2]->getContent(), "'/'");
            $tokenAst->setAttr($isToken ? "pattern" : "value", $pattern);

            return $tokenAst;

        } else {
            return false;
        }
    }

    private function rule(pars\TokenStream $tokenStream)
    {
        $annotations = $this->annotations($tokenStream);

        try {
            $tokens = $tokenStream->consumeExpected(self::ID, self::ARROW);
            $ruleAst = new pars\Ast("rule");
            $ruleAst->setAttr("name", $tokens[0]->getContent());

            foreach ($annotations as $annot) {
                $ruleAst->addChild($annot);
            }

            $branches = $this->branches($tokenStream);
            if (empty($branches)) {
                return false;
            }

            foreach ($branches as $branchAst) {
                $ruleAst->addChild($branchAst);
            }

            $tokenStream->consumeExpected(self::SEMICOLON);

            return $ruleAst;

        } catch (\Exception $err) {
            return false;
        }

    }

    private function annotations(pars\TokenStream $tokenStream)
    {
        $annotations = [];

        while (true) {
            $tokens = $tokenStream->checkFor(self::AT, [self::GRAMMAR]);
            if ($tokens === false) {
                break;
            }
            $annotations[] = new pars\Ast("annot", $tokens[1]->getContent());
            $tokenStream->consumeMany(2);
        }

        return $annotations;
    }

    private function branches(pars\TokenStream $tokenStream)
    {
        $branches = [];

        while (true) {
            $branchAst = $this->branch($tokenStream);
            if ($branchAst !== false) {
                $branches[] = $branchAst;
            } else {
                return [];
            }
            if ($tokenStream->checkFor(self::PIPE) === false) {
                break;
            } else {
                $tokenStream->consume();
            }
        }

        return $branches;
    }

    private function branch(pars\TokenStream $tokenStream)
    {
        $branchAst = false;

        while (true) {

            $elemAst = $this->element($tokenStream);
            if ($elemAst === false) {
                return $branchAst;
            }

            if ($branchAst === false) {
                $branchAst = new pars\Ast("branch");
            }

            if ($elemAst) {
                $branchAst->addChild($elemAst);
            }

        }

        return $branchAst;
    }

    private function element(pars\TokenStream $tokenStream)
    {
        // Check for id:
        $tokens = $tokenStream->checkFor(self::ID, self::HASH);
        if ($tokens !== false) {
            $id = $tokens[0]->getContent();
            $tokenStream->consumeMany(2);
        } else {
            $id = null;
        }

        $elemAst = false;

        while (true) {

            $elemAst = $this->ruleId($tokenStream);
            if ($elemAst !== false) {
                break;
            }

            $elemAst = $this->keyword($tokenStream);
            if ($elemAst !== false) {
                break;
            }

            $elemAst = $this->group($tokenStream);
            if ($elemAst !== false) {
                break;
            }

            $elemAst = $this->tokenId($tokenStream);
            break;

        }

        if ($elemAst !== false) {
            if ($id !== null) {
                $elemAst->setAttr("id", $id);
            }
            return $this->multChecked($elemAst, $tokenStream);
        }

        return $elemAst;

    }

    private function multChecked(pars\Ast $elemAst, pars\TokenStream $tokenStream)
    {
        $mult = $this->multiplicity($tokenStream);
        if ($mult !== false) {
            switch ($mult) {
                case self::MULT_ZERO_TO_ONE:
                    $elemAst->setAttr("mult", "zero_to_one");
                    break;
                case self::MULT_MANY:
                    $elemAst->setAttr("mult", "many");
                    break;
                case self::MULT_ONE_TO_MANY:
                    $elemAst->setAttr("mult", "one_to_many");
                    break;
            }
        }

        return $elemAst;
    }

    private function ruleId(pars\TokenStream $tokenStream)
    {
        if ($tokenStream->checkFor(self::ID)) {

            $token = $tokenStream->consume();
            $ruleIdAst = new pars\Ast("rule_id");
            $ruleIdAst->setAttr("name", $token->getContent());

            return $ruleIdAst;

        } else {

            return false;

        }

    }

    private function keyword(pars\TokenStream $tokenStream)
    {
        if ($tokenStream->checkFor(self::STRING)) {

            $token = $tokenStream->consume();
            $keywordAst = new pars\Ast("keyword");
            $keywordAst->setAttr("name", trim($token->getContent(), "'/'"));

            return $keywordAst;

        } else {

            return false;

        }
    }

    private function group(pars\TokenStream $tokenStream)
    {
        try {

            $tokenStream->consumeExpected(self::PAR_OPEN);

            $groupAst = new pars\Ast("group");
            $branches = $this->branches($tokenStream);
            if (empty($branches)) {
                return false;
            }

            foreach ($branches as $branch) {
                $groupAst->addChild($branch);
            }

            $tokenStream->consumeExpected(self::PAR_CLOSE);

            return $groupAst;

        } catch (\Exception $error) {
            return false;
        }
    }

    private function tokenId(pars\TokenStream $tokenStream)
    {
        try {

            $tokenIds = $tokenStream->consumeExpected(self::TOKEN_ID);
            if (empty($tokenIds)) {
                return false;
            }
            $tokenIdAst = new pars\Ast("token_id");
            $tokenIdAst->setAttr("name", $tokenIds[0]->getContent());

            return $tokenIdAst;

        } catch (\Exception $error) {
            return false;
        }
    }

    const MULT_ZERO_TO_ONE = 1;
    const MULT_MANY = 2;
    const MULT_ONE_TO_MANY = 3;

    private function multiplicity(pars\TokenStream $tokenStream)
    {
        try {

            $tokens = $tokenStream->consumeExpected([
                self::QUESTION_MARK,
                self::ASTERISK,
                self::PLUS
            ]);

            switch ($tokens[0]->getType()) {
                case self::QUESTION_MARK:
                    return self::MULT_ZERO_TO_ONE;
                case self::ASTERISK:
                    return self::MULT_MANY;
                case self::PLUS:
                    return self::MULT_ONE_TO_MANY;
                default:
                    return false;
            }

        } catch (\Exception $error) {
            return false;
        }

    }

    private function createLexer()
    {
        $lexer = new pars\Lexer();

        $lexer->addCommentType("--", PHP_EOL);
        $lexer->addCommentType("(*", "*)");

        $lexer->addSymbol("|", self::PIPE);
        $lexer->addSymbol("(", self::PAR_OPEN);
        $lexer->addSymbol(")", self::PAR_CLOSE);
        $lexer->addSymbol("#", self::HASH);
        $lexer->addSymbol("*", self::ASTERISK);
        $lexer->addSymbol("+", self::PLUS);
        $lexer->addSymbol("?", self::QUESTION_MARK);
        $lexer->addSymbol(";", self::SEMICOLON);
        $lexer->addSymbol("->", self::ARROW);
        $lexer->addSymbol("@", self::AT);

        $lexer->addKeyword("grammar");
        $lexer->addKeyword("token");
        $lexer->addKeyword("symbol");

        $lexer->addTerminal("/[A-Z_][A-Z0-9_]*/", self::TOKEN_ID);
        $lexer->addTerminal("/[a-z_][a-z0-9_]*/", self::ID);

        $lexer->addStringType("/", "\\/"); // <-- for use in regular expressions
        $lexer->addStringType("'", "\\''");

        return $lexer;
    }


}