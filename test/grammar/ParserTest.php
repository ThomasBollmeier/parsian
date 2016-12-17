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

ID = /[a-z][a-z0-9-\?]*/;


-- Rules:

boolean_expr = conjunction 'OR' boolean_expr; 


GRAMMAR;

        $parser = new Parser();
        $ast = $parser->parseString($grammar);


        $this->assertNotNull($ast);
        $this->assertEquals(2, count($ast->getChildren()));

        print ($ast->toXml());

    }

}