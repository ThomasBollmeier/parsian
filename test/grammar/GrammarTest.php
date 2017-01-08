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

use tbollmeier\parsian\Lexer;
use tbollmeier\parsian\StringCharInput;
use tbollmeier\parsian\TokenStream;
use tbollmeier\parsian\Ast;
use tbollmeier\parsian\grammar\Terminal as Term;
use tbollmeier\parsian\grammar\ZeroToOne as Opt;
use tbollmeier\parsian\grammar\Many as Many;
use tbollmeier\parsian\grammar\Sequence as Seq;
use tbollmeier\parsian\grammar\Alternatives as Alt;
use tbollmeier\parsian\grammar\Rule;
use tbollmeier\parsian\grammar\RuleRef;

class GrammarTest extends TestCase
{
    public function testExpressions()
    {
        $grammar = $this->createGrammar();

        $code = <<<CODE
a and ( b or ~c )
CODE;

        $lexer = $this->createLexer();
        $stream = new TokenStream($lexer->createTokenInput(new StringCharInput($code)));

        $nodes = $grammar->translate($stream);

        self::assertNotFalse($nodes);

        print(($nodes[0])->toXml());

    }

    private function createGrammar()
    {
        $disj = new Rule("disj",
            (new Seq())
                ->add(new RuleRef("conj", "elem"))
                ->add(new Many(
                    (new Seq())
                        ->add(new Term("OR"))
                        ->add(new RuleRef("conj", "elem")))));

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

        $conj = new Rule("conj",
            (new Seq())
                ->add(new RuleRef("expr", "elem"))
                ->add(new Many(
                    (new Seq())
                        ->add(new Term("AND"))
                        ->add(new RuleRef("expr", "elem")))));

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

        $expr = new Rule("expr",
            (new Seq())
                ->add(new Opt(new Term("NOT", "neg")))
                ->add((new Alt())
                    ->add(new Term("IDENT", "content"))
                    ->add((new Seq())
                            ->add(new Term("PAR_OPEN"))
                            ->add(new RuleRef("disj", "content"))
                            ->add(new Term("PAR_CLOSE")))));

        $expr->setCustomAstFn(function (Ast $ast) {

            $contents = $ast->getChildrenById("content");
            $res = $contents[0];
            $res->clearId();

            if (!empty($ast->getChildrenById("neg"))) {
                $res->setAttr("negated", "true");
            }

            return $res;
        });

        return $disj;
    }

    private function createLexer()
    {
        $lexer = new Lexer();
        $lexer->addKeyword("or");
        $lexer->addKeyword("and");
        $lexer->addSymbol("(", "PAR_OPEN");
        $lexer->addSymbol(")", "PAR_CLOSE");
        $lexer->addSymbol("~", "NOT");
        $lexer->addTerminal("/[a-z]+/", "IDENT");

        return $lexer;
    }

}