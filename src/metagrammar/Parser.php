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

namespace tbollmeier\parsian\metagrammar;

use tbollmeier\parsian\grammar\Grammar;
use tbollmeier\parsian\output\Ast;
use tbollmeier\parsian\Parser as PParser;


class Parser extends PParser
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
    const LITERAL = "LITERAL";
    const COMMENT = "COMMENT";
    const TOKEN = "TOKEN";
    const SYMBOL = "SYMBOL";
    const ROOT = "ROOT";

    public function __construct()
    {
    	parent::__construct();

    	$this->configLexer();
    	$this->configGrammar();

    }

    private function configLexer()
    {
        $lexer = $this->getLexer();

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

        $lexer->addKeyword("root");
        $lexer->addKeyword("literal");
        $lexer->addKeyword("comment");
        $lexer->addKeyword("token");
        $lexer->addKeyword("symbol");

        $lexer->addTerminal("/[A-Z_][A-Z0-9_]*/", self::TOKEN_ID);
        $lexer->addTerminal("/[a-z_][a-z0-9_]*/", self::ID);

        $lexer->addStringType("/", "\\/"); // <-- for use in regular expressions
        $lexer->addStringType("'", "\\''");
        $lexer->addStringType('"', '\\"');

    }

    private function configGrammar()
    {
        $g = $this->getGrammar();

        $g->rule('metagrammar',
            $g->oneOrMore($g->alt()
                ->add($g->ruleRef('comment_def'))
                ->add($g->ruleRef('literal_def'))
                ->add($g->ruleRef('symbol_def'))
                ->add($g->ruleRef('token_def'))
                ->add($g->ruleRef('rule_def'))),
            true);

        $g->rule('comment_def',
            $g->seq()
                ->add($g->term(self::COMMENT))
                ->add($g->term(self::STRING)) // <-- start chars
                ->add($g->term(self::STRING)) // <-- end chars
                ->add($g->term(self::SEMICOLON)));

        $g->rule('literal_def',
            $g->seq()
                ->add($g->term(self::LITERAL))
                ->add($g->term(self::STRING)) // <-- delimiter
                ->add($g->opt($g->term(self::STRING, 'esc'))) // <-- escape chars
                ->add($g->term(self::SEMICOLON)));

        $g->rule('symbol_def',
            $g->seq()
                ->add($g->term(self::SYMBOL))
                ->add($g->term(self::TOKEN_ID))
                ->add($g->term(self::STRING))
                ->add($g->term(self::SEMICOLON)));

        $g->rule('token_def',
            $g->seq()
                ->add($g->term(self::TOKEN))
                ->add($g->term(self::TOKEN_ID))
                ->add($g->term(self::STRING))
                ->add($g->term(self::SEMICOLON)));

        $g->rule('rule_def',
            $g->seq()
                ->add($g->opt($g->ruleRef('annot', 'root')))
                ->add($g->term(self::ID, 'rule_name'))
                ->add($g->term(self::ARROW))
                ->add($g->ruleRef('branch', 'content'))
                ->add($g->term(self::SEMICOLON)));

        $g->rule('annot',
            $g->seq()
                ->add($g->term(self::AT))
                ->add($g->term(self::ROOT)));

        $g->rule('branch',
            $g->seq()
                ->add($g->ruleRef('sequence'))
                ->add($g->many($g->seq()
                    ->add($g->term(self::PIPE))
                    ->add($g->ruleRef('sequence')))));

        $g->rule('sequence',
            $g->oneOrMore($g->seq()
                ->add($g->alt()
                    ->add($g->ruleRef('group'))
                    ->add($g->ruleRef('atom')))
                ->add($g->opt($g->alt()
                    ->add($g->term(self::QUESTION_MARK, 'mult'))
                    ->add($g->term(self::ASTERISK, 'mult'))
                    ->add($g->term(self::PLUS, 'mult'))))));

        $g->rule('group',
            $g->seq()
                ->add($g->term(self::PAR_OPEN))
                ->add($g->ruleRef('branch'))
                ->add($g->term(self::PAR_CLOSE)));

        $g->rule('atom',
            $g->seq()
                ->add($g->opt($g->seq()
                    ->add($g->term(self::ID, 'id'))
                    ->add($g->term(self::HASH))))
                ->add($g->alt()
                    ->add($g->term(self::ID, 'rule'))
                    ->add($g->term(self::TOKEN_ID, 'token'))
                    ->add($g->term(self::STRING, 'keyword'))));

        $this->configTransforms($g);

    }

    private function configTransforms(Grammar $g)
    {
        $g->setCustomRuleAst('comment_def', function (Ast $ast)
        {
            $res = new Ast('comment_def');
            $children = $ast->getChildren();

            $res->addChild(new Ast('begin', $this->strip($children[1]->getText())));
            $res->addChild(new Ast('end', $this->strip($children[2]->getText())));

            return $res;
        });

        $g->setCustomRuleAst('literal_def', function (Ast $ast)
        {
            $res = new Ast('literal_def');
            $children = $ast->getChildren();

            $res->addChild(new Ast('delim', $this->strip($children[1]->getText())));
            if (!empty($ast->getChildrenById('esc'))) {
                $res->addChild(new Ast('esc', $this->strip($children[2]->getText())));
            }

            return $res;
        });

        $g->setCustomRuleAst('symbol_def', function (Ast $ast)
        {
            $res = new Ast('symbol_def');
            $children = $ast->getChildren();

            $res->addChild(new Ast('name', $children[1]->getText()));
            $res->addChild(new Ast('value', $this->strip($children[2]->getText())));

            return $res;
        });

        $g->setCustomRuleAst('token_def', function (Ast $ast)
        {
            $res = new Ast('token_def');
            $children = $ast->getChildren();

            $res->addChild(new Ast('name', $children[1]->getText()));
            $res->addChild(new Ast('value', $this->strip($children[2]->getText())));

            return $res;
        });

        $g->setCustomRuleAst('rule_def', function (Ast $ast) {
            $res = new Ast('rule_def');

            $name = ($ast->getChildrenById('rule_name')[0])->getText();
            $res->addChild(new Ast('name', $name));

            if (!empty($ast->getChildrenById('root'))) {
                $res->setAttr('x-root', "true");
            }

            $content = $ast->getChildrenById('content')[0];
            $content->clearId();

            $res->addChild($content);

            return $res;
        });

        $g->setCustomRuleAst('branch', function (Ast $ast)
        {
            $seqs = [];
            foreach ($ast->getChildren() as $child) {
                if ($child->getName() !== 'terminal') {
                    $seqs[] = $child;
                }
            }

            if (count($seqs) > 1) {
                $res = new Ast('branch');
                foreach ($seqs as $seq) {
                    $res->addChild($seq);
                }
                return $res;
            } else {
                return $seqs[0];
            }

        });

        $g->setCustomRuleAst('sequence', function (Ast $ast)
        {
            $res = new Ast('sequence');

            $prev = null;
            foreach ($ast->getChildren() as $child) {
                if (empty($child->getId())) {
                    $res->addChild($child);
                    $prev = $child;
                } else {
                    switch ($child->getAttr('type')) {
                        case self::ASTERISK:
                            $mult = "many";
                            break;
                        case self::QUESTION_MARK:
                            $mult = "opt";
                            break;
                        case self::PLUS:
                            $mult = "one-or-more";
                            break;
                        default:
                            $mult = "";
                    }
                    $prev->setAttr('x-mult', $mult);
                }
            }

            if (count($res->getChildren()) === 1) {
                $res = $res->getChildren()[0];
            }

            return $res;
        });


        $g->setCustomRuleAst('group', function (Ast $ast)
        {
            return $ast->getChildren()[1];
        });

        $g->setCustomRuleAst('atom', function (Ast $ast) {

            $content = null;
            $id = null;

            foreach ($ast->getChildren() as $child) {
                switch ($child->getId()) {
                    case 'id':
                        $id = $child->getText();
                        break;
                    case '':
                        break;
                    default:
                        $content = $child;
                        $content->clearId();
                }
            }

            if ($id !== null) {
                $content->setAttr('x-id', $id);
            }

            return $content;
        });

        $g->setCustomTermAst(self::TOKEN_ID, function (Ast $ast)
        {
            return $ast->getId() === 'token' ?
                new Ast('token', $ast->getText()) : $ast;
        });

        $g->setCustomTermAst(self::ID, function (Ast $ast)
        {
            return $ast->getId() === 'rule' ?
                new Ast('rule', $ast->getText()) : $ast;
        });

        $g->setCustomTermAst(self::STRING, function (Ast $ast)
        {
            return $ast->getId() === 'keyword' ?
                new Ast('keyword', $this->strip($ast->getText())) : $ast;
        });

    }

    private function strip($text)
    {
        $len = strlen($text);
        return substr($text, 1, $len - 2);
    }

}
