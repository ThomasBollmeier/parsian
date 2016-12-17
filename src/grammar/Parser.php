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
    const ASSIGN = "ASSIGN";
    const AT = "AT";
    const TOKEN_ID = "TOKEN_ID";
    const ID = "ID";
    const STRING = "STRING";


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
        $parser = new pars\Parser($tokenInput);
        $parser->openTokenInput();

        $ast = $this->grammar($parser);

        $parser->closeTokenInput();

        return $ast;
    }

    private function grammar(pars\Parser $parser)
    {
        $grammar = new pars\Ast("grammar");

        while (true) {

            $token = $this->token($parser);
            if ( $token !== false) {
                $grammar->addChild($token);
                continue;
            }

            $rule = $this->rule($parser);
            if ( $rule !== false) {
                $grammar->addChild($rule);
                continue;
            }

            break;
        }

        return $grammar;
    }

    private function token(pars\Parser $parser)
    {
        if ($parser->checkFor(
            self::TOKEN_ID,
            self::ASSIGN,
            self::STRING,
            self::SEMICOLON)) {

            $tokenAst = new pars\Ast("token");
            $tokens = $parser->consumeMany(4);

            $tokenAst->setAttr("name", $tokens[0]->getContent());
            $pattern = trim($tokens[2]->getContent(), "'/'");
            $tokenAst->setAttr("pattern", $pattern);

            return $tokenAst;

        } else {
            return false;
        }
    }

    private function rule(pars\Parser $parser)
    {
        // TODO: annotation handling...

        try {
            $tokens = $parser->consumeExpected(self::ID, self::ASSIGN);
            $ruleAst = new pars\Ast("rule");
            $ruleAst->setAttr("name", $tokens[0]->getContent());

            $branches = $this->branches($parser);
            if (empty($branches)) {
                return false;
            }

            foreach ($branches as $branchAst) {
                $ruleAst->addChild($branchAst);
            }

            $parser->consumeExpected(self::SEMICOLON);

            return $ruleAst;

        } catch (\Exception $err) {
            return false;
        }

    }

    private function branches(pars\Parser $parser)
    {
        $branches = [];

        while (true) {
            $branchAst = $this->branch($parser);
            if ($branchAst !== false) {
                $branches[] = $branchAst;
            } else {
                return [];
            }
            if ($parser->checkFor(self::PIPE) === false) {
                break;
            } else {
                $parser->consume();
            }
        }

        return $branches;
    }

    private function branch(pars\Parser $parser)
    {
        $branchAst = false;

        while (true) {

            $elemAst = $this->element($parser);
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

    private function element(pars\Parser $parser)
    {
        $elemAst = $this->ruleId($parser);
        if ($elemAst !== false) {
            return $elemAst;
        }

        $elemAst = $this->keyword($parser);
        if ($elemAst !== false) {
            return $elemAst;
        }

        return false;

    }

    private function ruleId(pars\Parser $parser)
    {
        if ($parser->checkFor(self::ID)) {

            $token = $parser->consume();
            $ruleIdAst = new pars\Ast("rule_id");
            $ruleIdAst->setAttr("name", $token->getContent());

            return $ruleIdAst;

        } else {

            return false;

        }

    }

    private function keyword(pars\Parser $parser)
    {
        if ($parser->checkFor(self::STRING)) {

            $token = $parser->consume();
            $keywordAst = new pars\Ast("keyword");
            $keywordAst->setAttr("name", trim($token->getContent(), "'/'"));

            return $keywordAst;

        } else {

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
        $lexer->addSymbol("=", self::ASSIGN);
        $lexer->addSymbol("@", self::AT);

        $lexer->addKeyword("grammar");

        $lexer->addTerminal("/[A-Z_][A-Z0-9_]*/", self::TOKEN_ID);
        $lexer->addTerminal("/[a-z_][a-z0-9_]*/", self::ID);

        $lexer->addStringType("/", "\\/"); // <-- for use in regular expressions
        $lexer->addStringType("'", "\\''");

        return $lexer;
    }


}