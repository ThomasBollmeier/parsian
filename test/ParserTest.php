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

use PHPUnit\Framework\TestCase;
use tbollmeier\parsian\Parser;
use tbollmeier\parsian\output\Ast;

class ParserTest extends TestCase
{
    public function testExpressions()
    {
        $parser = new Parser();

        $this->configLexer($parser);
        $this->configGrammar($parser);

        $code = <<<CODE
a and ( b or ~c )
CODE;

        $ast = $parser->parseString($code);

        self::assertNotFalse($ast);
        print($ast->toXml() . PHP_EOL);

        $code = "a and or";

        $ast = $parser->parseString($code);

        self::assertFalse($ast);

        print ($parser->error() . PHP_EOL);


        //print(($ast)->toXml());

    }

    private function configGrammar(Parser $parser)
    {
        $g = $parser->getGrammar();

        $disj = $g->rule("disj",
            $g->seq()
                ->add($g->ruleRef("conj", "elem"))
                ->add($g->many(
                    $g->seq()
                        ->add($g->term("OR"))
                        ->add($g->ruleRef("conj", "elem")))), true);

        $disj->setCustomAstFn(function(Ast $ast) {

            $elems = $ast->getChildrenById("elem");
            if (count($elems) > 1) {
                $res = new Ast("or");
                foreach ($elems as $elem) {
                    $elem->clearId();
                    $res->addChild($elem);
                }
            } else {
                $res = $elems[0];
                $res->clearId();
            }

            return $res;
        });

        $conj = $g->rule("conj",
            $g->seq()
                ->add($g->ruleRef("expr", "elem"))
                ->add($g->many(
                    $g->seq()
                        ->add($g->term("AND"))
                        ->add($g->ruleRef("expr", "elem")))));

        $conj->setCustomAstFn(function(Ast $ast) {

            $elems = $ast->getChildrenById("elem");
            if (count($elems) > 1) {
                $res = new Ast("and");
                foreach ($elems as $elem) {
                    $elem->clearId();
                    $res->addChild($elem);
                }
            } else {
                $res = $elems[0];
                $res->clearId();
            }

            return $res;
        });

        $expr = $g->rule("expr",
            $g->seq()
                ->add($g->opt($g->term("NOT", "neg")))
                ->add($g->alt()
                    ->add($g->term("IDENT", "content"))
                    ->add($g->seq()
                            ->add($g->term("PAR_OPEN"))
                            ->add($g->ruleRef("disj", "content"))
                            ->add($g->term("PAR_CLOSE")))));

        $expr->setCustomAstFn(function (Ast $ast) {

            $contents = $ast->getChildrenById("content");
            $res = $contents[0];
            $res->clearId();

            if (!empty($ast->getChildrenById("neg"))) {
                $res->setAttr("negated", "true");
            }

            return $res;
        });

        $g->setCustomTermAst("IDENT", function($ast)
        {
           return new Ast("id", $ast->getText());
        });

    }

    private function configLexer(Parser $parser)
    {
        $lexer = $parser->getLexer();
        $lexer->addKeyword("or");
        $lexer->addKeyword("and");
        $lexer->addSymbol("(", "PAR_OPEN");
        $lexer->addSymbol(")", "PAR_CLOSE");
        $lexer->addSymbol("~", "NOT");
        $lexer->addTerminal("/[a-z]+/", "IDENT");
    }

}