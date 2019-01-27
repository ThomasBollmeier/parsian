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

use tbollmeier\parsian\codegen\FileOutput;
use tbollmeier\parsian\metagrammar\Parser;
use tbollmeier\parsian\codegen\CodeGenerator;
//use tbollmeier\parsian\codegen\StdOutput;


class CodeGeneratorTest extends TestCase
{

    public function testCodeGeneration()
    {
        $grammar = <<<GRAMMAR
(* 
Logical expression grammar
Author: Thomas Bollmeier 2017 <entwickler@tbollmeier.de>
*)

-- Lexical elements:

comment '(*' '*)' enable_nesting;

literal '"';

symbol PAR_OPEN '(';
symbol PAR_CLOSE ')';
symbol NOT '~';

token IDENT /[a-z]+/;

-- Production rules

@root
disj -> left#conj ( 'or' right#conj )* => 
{
    :name "OR"
    :attrs [{:key "category" :value "logic-expr"}]
    :children [#left #right {:name "demo" :children .conj}]
};

conj -> expr ( 'and' expr )*;

expr -> neg#NOT? ( content#IDENT | PAR_OPEN content#disj PAR_CLOSE );

dict -> PAR_OPEN key_value* PAR_CLOSE => {
    :name "dict"
    :children #key_value
};

key_value -> key#IDENT value#expr => {
    :name "entry"
    :children [{:name "key" :text #key.text } #value]
};

GRAMMAR;

        $parser = new Parser();

        $ast = $parser->parseString($grammar);
        self::assertNotFalse($ast, $parser->error());

        $generator = new CodeGenerator("DemoParser");
        $generator->setHeaderCommentFile("demo_license.txt");
        $generator->generate($ast);

    }

    public function testCodeGenFromFile()
    {
        $parser = new Parser();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . "logic.parsian";

        $ast = $parser->parseFile($filePath);
        self::assertNotFalse($ast, $parser->error());

        $generator = new CodeGenerator("DemoParser");

        $generator->generate($ast);

        $output = new FileOutput("DemoParser.php");
        $generator->generate($ast, $output);

        require __DIR__ . "/DemoParser.php";
        $demoParser = new \DemoParser();

        $code =<<<CODE
(a or b) and ~c
CODE;
        $ast = $demoParser->parseString($code);
        self::assertNotFalse($ast, $demoParser->error());

        print($ast->toXml());

    }

}