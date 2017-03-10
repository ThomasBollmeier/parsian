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

$short = "p:n:";
$long = ["parser:", "namespace:"];
$options = getopt($short, $long);
$args = argsWithoutOptions($argv);

$parserName = getOptValue($options, "p", "parser", "MyParser");
$namespace = getOptValue($options, "n", "namespace", "");

$grammarPath = empty($args) ? "" : $args[0];

list($result, $error) = Api::generateParserFromGrammar(
        $grammarPath, $parserName, $namespace);

if (!$result) {
    print($error);
}