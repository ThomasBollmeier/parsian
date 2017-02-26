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


class CodeGenerator
{
    private $namespace;
    private $parserName;
    private $content;

    private $output;
    private $indentLevel;
    private $indentSize;

    public function __construct($parserName, $namespace = "")
    {
        $this->parserName = $parserName;
        $this->namespace = $namespace;
        $this->content = [
            "comments" => [],
            "literals" => [],
            "symbols" => [],
            "tokens" => [],
            "keywords" => [],
            "rules" => [],
            "alternatives" => [],
            "sequences" => []
        ];

    }

    public function generate($ast, Output $output=null)
    {
        $this->output = $output ?: new StdOutput();
        $this->indentLevel = 0;
        $this->indentSize = 4;

        $this->collectGrammarElements($ast);

        $this->writeln("<?php");
        if (!empty($this->namespace)) {
            $this->writeln("namespace {$this->namespace};");
        }
        $this->writeln();
        $this->writeln("use tbollmeier\\parsian as parsian;");
        $this->writeln();
        $this->writeln();
        $this->writeln("class {$this->parserName} extends parsian\\Parser");
        $this->writeln("{");
        $this->indent();
        $this->genConstructor();
        $this->writeln();
        $this->genLexerConfig();
        $this->writeln();
        $this->genGrammarConfig();
        $this->writeln();
        $this->genAlternatives();
        $this->writeln();
        $this->genSequences();
        $this->writeln();
        $this->dedent();
        $this->writeln("}");

    }

    private function collectGrammarElements($grammarXml)
    {

    }

    private function genConstructor()
    {
        $this->writeln("public function __construct()");
        $this->writeln("{");
        $this->indent();
        $this->writeln("parent::__construct();");
        $this->writeln();
        $this->writeln("\$this->configLexer();");
        $this->writeln("\$this->configGrammar();");
        $this->dedent();
        $this->writeln("}");

    }

    private function genLexerConfig()
    {
        $this->writeln("private function configLexer()");
        $this->writeln("{");
        $this->indent();
        $this->writeln();
        $this->writeln("\$lexer = \$this->getLexer();");
        $this->writeln();

        foreach ($this->content["comments"] as $comment) {
            $begin = $this->quoteEsc($comment["begin"]);
            $end = $this->quoteEsc($comment["end"]);
            $line = "\$lexer->addCommentType(\"{$begin}\", \"{$end}\");";
            $this->writeln($line);
        }
        $this->writeln();

        foreach ($this->content["literals"] as $lit) {
            $delim = $this->quoteEsc($lit["delim"]);
            if (isset($lit["esc"])) {
                $esc = $this->quoteEsc($lit["esc"]);
                $line = "\$lexer->addStringtType(\"{$delim}\", \"{$esc}\");";
            } else {
                $line = "\$lexer->addStringtType(\"{$delim}\");";
            }
            $this->writeln($line);
        }
        $this->writeln();

        foreach ($this->content["symbols"] as $sym) {
            $name = $this->quoteEsc($sym["name"]);
            $value = $this->quoteEsc($sym["value"]);
            $line = "\$lexer->addSymbol(\"{$value}\", \"{$name}\");";
            $this->writeln($line);
        }
        $this->writeln();

        foreach ($this->content["tokens"] as $tok) {
            $name = $this->quoteEsc($tok["name"]);
            $value = $this->quoteEsc($tok["value"]);
            $line = "\$lexer->addTerminal(\"/{$value}/\", \"{$name}\");";
            $this->writeln($line);
        }
        $this->writeln();

        foreach ($this->content["keywords"] as $kw) {
            $keyw = $this->quoteEsc($kw);
            $line = "\$lexer->addKeyword(\"{$keyw}\");";
            $this->writeln($line);
        }
        $this->writeln();

        $this->dedent();
        $this->writeln("}");

    }

    private function quoteEsc($text)
    {
        return str_replace('"', '\\"', $text);
    }

    private function genGrammarConfig()
    {
        $this->writeln("private function configGrammar()");
        $this->writeln("{");
        $this->indent();
        $this->writeln();
        $this->writeln("\$grammar = \$this->getGrammar();");
        $this->writeln();
        
        foreach ($this->content["rules"] as $rule) {
            $ruleName = $rule["name"];
            $elemRef = $this->elementRef($rule["content"]);
            $isRoot = $rule["is_root"];
            $this->writeln("\$grammar->rule(\"{$ruleName}\",");
            $this->indent();
            $this->writeln("{$elemRef},");
            $this->writeln("{$isRoot});");
            $this->dedent();
        }
        $this->writeln();
        
        $this->dedent();
        $this->writeln("}");

    }

    private function elementRef($element)
    {
        $res = "";
        $type = $element["type"];
        $name = $element["name"];
        $mult = $element["mult"];

        if ($type == "alt_ref" || $type == "seq_ref") {
            $res = "\$this->{$name}()";
        } elseif ($type == "rule") {
            if (isset($element["id"])) {
                $id = $element["id"];
                $res = "\$grammar->ruleRef(\"{$name}\", \"{$id}\")";
            } else {
                $res = "\$grammar->ruleRef(\"{$name}\")";
            }
        } elseif ($type == "token") {
            if (isset($element["id"])) {
                $id = $element["id"];
                $res = "\$grammar->term(\"{$name}\", \"{$id}\")";
            } else {
                $res = "\$grammar->term(\"{$name}\")";
            }
        } elseif ($type == "keyword") {
            $kw = strtoupper($name);
            if (isset($element["id"])) {
                $id = $element["id"];
                $res = "\$grammar->term(\"{$kw}\", \"{$id}\")";
            } else {
                $res = "\$grammar->term(\"{$kw}\")";
            }
        }

        if ($mult == "opt") {
            $res = "\$grammar->opt({$res})";
        } elseif ($mult == "many") {
            $res = "\$grammar->many({$res})";
        } elseif ($mult == "one-or-more") {
            $res = "\$grammar->oneOrMore({$res})";
        }

        return $res;

    }

    private function genAlternatives()
    {

    }

    private function genSequences()
    {

    }

    private function writeln($text="")
    {
        if (!empty($text)) {
            $padding = "";
            for ($i=0; $i<$this->indentLevel; $i++) {
                $padding .= " ";
            }
            $this->output->writeln($padding . $text);
        } else {
            $this->output->writeln($text);
        }

    }

    public function indent()
    {
        $this->indentLevel += $this->indentSize;
    }

    public function dedent()
    {
        $this->indentLevel -= $this->indentSize;
    }

}
