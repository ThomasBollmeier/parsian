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
                ->add($g->ruleRef('comment'))
                ->add($g->ruleRef('literal'))
                ->add($g->ruleRef('symbol'))
                ->add($g->ruleRef('token'))
                ->add($g->ruleRef('rule'))),
            true);

        $g->rule('comment',
            $g->seq()
                ->add($g->term(self::COMMENT))
                ->add($g->term(self::STRING)) // <-- start chars
                ->add($g->term(self::STRING)) // <-- end chars
                ->add($g->term(self::SEMICOLON)));

        $g->rule('literal',
            $g->seq()
                ->add($g->term(self::LITERAL))
                ->add($g->term(self::STRING)) // <-- delimiter
                ->add($g->term(self::STRING)) // <-- escape chars
                ->add($g->term(self::SEMICOLON)));

        $g->rule('symbol',
            $g->seq()
                ->add($g->term(self::SYMBOL))
                ->add($g->term(self::TOKEN_ID))
                ->add($g->term(self::STRING))
                ->add($g->term(self::SEMICOLON)));

        $g->rule('token',
            $g->seq()
                ->add($g->term(self::TOKEN))
                ->add($g->term(self::TOKEN_ID))
                ->add($g->term(self::STRING))
                ->add($g->term(self::SEMICOLON)));

        $g->rule('rule',
            $g->seq()
                ->add($g->opt($g->ruleRef('annot', 'root')))
                ->add($g->term(self::ID, 'rule_name'))
                ->add($g->term(self::ARROW))
                ->add($g->ruleRef('branch'))
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

    }

}
