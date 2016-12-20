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

use PHPUnit\Framework\TestCase;

use tbollmeier\parsian\grammar\Parser;

class ParserTest extends TestCase
{
    public function testParseString()
    {
        $grammar = <<<GRAMMAR

-- Tokens:

token ID /[a-z][a-z0-9-\?]*/;
symbol PAR_OPEN '(';
symbol PAR_CLOSE ')';

-- Rules:

@grammar
boolean_expr -> conjunction ( 'or' others#conjunction )*;
 
conjunction -> group ('and' group)*;

group -> neg#'not'? ( PAR_OPEN b#boolean_expr PAR_CLOSE | a#atomic );

atomic -> left#ID ('eq' | 'ne' | 'gt' | 'ge' | 'lt' | 'le' ) right#ID; 

GRAMMAR;

        $parser = new Parser();
        $ast = $parser->parseString($grammar);


        $this->assertNotNull($ast);
        $this->assertEquals(7, count($ast->getChildren()));

        print ($ast->toXml());

    }

}