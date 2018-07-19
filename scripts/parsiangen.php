#!/usr/bin/env php
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


spl_autoload_register(function ($qualifiedName) {

    $nameParts = explode("\\", $qualifiedName);

    if ((count($nameParts) < 2 ||
        $nameParts[0] !== "tbollmeier" ||
        $nameParts[1] !== "parsian")) {

        return;

    }

    array_shift($nameParts);
    array_shift($nameParts);

    $path = __DIR__ .  DIRECTORY_SEPARATOR . "..";
    $path .= DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR;
    $path .= implode(DIRECTORY_SEPARATOR, $nameParts) . ".php";
    $path = realpath($path);

    require $path;

});


use tbollmeier\parsian\codegen\Api;
use tbollmeier\parsian\codegen\FileOutput;


function argsWithoutOptions($argv)
{
    $args = [];

    array_shift($argv);
    while (count($argv) > 0) {
        $arg = array_shift($argv);
        if ($arg[0] !== "-") {
            $args[] = $arg;
        }
    }

    return $args;
}

function getOptValue($options, $shortName, $longName, $default)
{
    if (array_key_exists($shortName, $options)) {
        return $options[$shortName];
    } elseif (array_key_exists($longName, $options)) {
        return $options[$longName];
    } else {
        return $default;
    }
}

function printHelp()
{
    $help = <<<HELP
Syntax: <path_to_php> parsiangen.php [options] <grammar_file>

Generates a PHP parser class from a grammar definition file.

Available options:
    
    -p<parserName>, --parser=<parserName>
        set name of generated parser class to <parserName>
    
    -n<namespace>, --namespace=<namespace>
        set namespace of generated parser class to <namespace>
        
    -o<parser_file>, --out=<parser_file>
        write output to <parser_file>
        
    -c<comment_file> --comment-file=<comment_file>
        
    -h, --help: 
        show this info


HELP;
    print($help);
}

$short = "p:n:o:c:h";
$long = ["parser:", "namespace:", "out:", "comment-file:", "help"];
$options = getopt($short, $long);
$args = argsWithoutOptions($argv);

$help = getOptValue($options, "h", "help", "nohelp");
if ($help !== "nohelp") {
    printHelp();
    exit(0);
}

$parserName = getOptValue($options, "p", "parser", "MyParser");
$namespace = getOptValue($options, "n", "namespace", "");
$parserFile = getOptValue($options, "o", "out", "");
$commentFile = getOptValue($options, "c", "comment-file", "");

$grammarPath = empty($args) ? "" : $args[0];

$output = empty($parserFile) ? null : new FileOutput($parserFile);

list($result, $error) = Api::generateParserFromGrammar(
        $grammarPath, $parserName, $namespace, $output, $commentFile);

if (!$result) {
    print($error);
}