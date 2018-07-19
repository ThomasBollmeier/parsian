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

namespace tbollmeier\parsian\codegen;

use tbollmeier\parsian\metagrammar\Parser as MetaGrammarParser;


class Api
{
    /**
     * Generate a parser from a grammar definition
     *
     * @param string $grammarFilePath path of grammar file
     * @param string $parserName name of parser class
     * @param string $namespace namespace of parser class
     * @param Output $output output to be written to
     * @param string $pathToHeaderComment path to file that contains header comment
     * @return mixed[] [true, ""] in case of success, [false, <error_message>] in error cases
     */
    static function generateParserFromGrammar(
        string $grammarFilePath,
        string $parserName="MyParser",
        string $namespace="",
        Output $output=null,
        string $pathToHeaderComment="")
    {
        $parser = new MetaGrammarParser();

        $ast = !empty($grammarFilePath) ?
            $parser->parseFile($grammarFilePath) :
            $parser->parseStdin();

        if ($ast !== false) {

            $generator = new CodeGenerator($parserName, $namespace);
            $generator->setHeaderCommentFile($pathToHeaderComment);
            $generator->generate($ast, $output);

            return [true, ""];

        } else {

            return [false, $parser->error()];

        }
    }

}