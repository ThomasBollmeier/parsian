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

use tbollmeier\parsian\metagrammar\Parser;

class ParserTest extends TestCase
{

    public function testExpressions()
    {
        $grammar = <<<GRAMMAR
(* 
Logical expression grammar
Author: Thomas Bollmeier 2017 <entwickler@tbollmeier.de>
*)

-- Lexical elements:

comment '(*' '*)';

literal '"';

symbol PAR_OPEN '(';
symbol PAR_CLOSE ')';
symbol NOT '~';

token IDENT /[a-z]+/;

-- Production rules

@root
disj -> conj ( 'or' conj )*;

conj -> expr ( 'and' expr )*;

expr -> neg#NOT? ( content#IDENT | PAR_OPEN content#disj PAR_CLOSE );

GRAMMAR;

        $parser = new Parser();

        $ast = $parser->parseString($grammar);
        self::assertNotFalse($ast, $parser->error());

        //print ($ast->toXml());

    }
    
    public function testExpressionsWithTransformations() {
        
        $grammar = <<<GRAMMAR
(* 
Logical expression grammar
Author: Thomas Bollmeier 2017 <entwickler@tbollmeier.de>
*)

-- Lexical elements:

comment '(*' '*)';

literal '"';

symbol PAR_OPEN '(';
symbol PAR_CLOSE ')';
symbol NOT '~';

token IDENT /[a-z]+/;

-- Production rules

@root
disj -> left#conj ( 'or' right#conj )* 
=>
{
    :name "or"
    :children [#left #right]
};

conj -> expr ( 'and' expr )*;

expr -> neg#NOT? ( content#IDENT | PAR_OPEN content#disj PAR_CLOSE );

fun -> 'fn' name#IDENT PAR_OPEN PAR_CLOSE;

GRAMMAR;

        $parser = new Parser();

        $ast = $parser->parseString($grammar);
        self::assertNotFalse($ast, $parser->error());

        print ($ast->toXml());

        
    }
    

}