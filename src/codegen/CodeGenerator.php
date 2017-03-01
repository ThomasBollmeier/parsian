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

use tbollmeier\parsian\output\Ast;
use tbollmeier\parsian\output\Visitor;


class CodeGenerator implements Visitor
{
    private $namespace;
    private $parserName;
    private $content;

    private $output;
    private $indentLevel;
    private $indentSize;

    private $elemStack;

    public function __construct($parserName, $namespace = "")
    {
        $this->parserName = $parserName;
        $this->namespace = $namespace;
        $this->content = [];
        $this->elemStack = [];
    }

    public function generate(Ast $grammar, Output $output=null)
    {
        $this->output = $output ?: new StdOutput();
        $this->indentLevel = 0;
        $this->indentSize = 4;

        $this->collectGrammarElements($grammar);

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

    private function collectGrammarElements(Ast $grammar)
    {
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

        $this->elemStack = [];

        $grammar->accept($this);

    }

    public function enter(Ast $ast)
    {
        $mult = $ast->hasAttr("x-mult") ?
            $ast->getAttr("x-mult") : null;

        $name = $ast->getName();

        switch ($name) {
            case "comment_def":
                $data = [
                    "begin" => "",
                    "end" => ""
                ];
                break;
            case "literal_def":
                $data = [
                    "delim" => "",
                    "esc" => null
                ];
                break;
            case "symbol_def":
            case "token_def":
                $data = [
                    "name" => "",
                    "value" => ""
                ];
                break;
            case "rule_def":
                $isRoot = $ast->hasAttr("x-root");
                $data = [
                    "name" => "",
                    "content" => null,
                    "is_root" => $isRoot
                ];
                break;
            case "branch":
                $data = [
                    "type" => "alt",
                    "name" => $this->createAltName(),
                    "choices" => [],
                    "mult" => $mult
                ];
                break;
            case "sequence":
                $data = [
                    "type" => "seq",
                    "name" => $this->createSeqName(),
                    "elements" => [],
                    "mult" => $mult
                ];
                break;
            case "token":
            case "rule":
            case "keyword":
                $id = $ast->hasAttr("x-id") ?
                    $ast->getAttr("x-id") : null;
                $data = [
                    "type" => $name,
                    "name" => "",
                    "id" => $id,
                    "mult" => $mult
                ];
                break;
            default:
                $data = null;
        }

        $this->elemStack[] = [$name, $data];

    }

    public function leave(Ast $ast)
    {
        // TODO implement
    }

    private function createAltName() : string
    {
        // TODO implement
        return "";
    }

    private function createSeqName() : string
    {
        // TODO implement
        return "";
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
        $alts = $this->content["alternatives"];
        foreach ($alts as $alt) {
            $this->genAlternative($alt);
            $this->writeln();
        }
    }

    private function genAlternative($alt)
    {
        $name = $alt["name"];
        $this->writeln("private function {$name}()");
        $this->writeln("{");
        $this->indent();
        $this->writeln("\$grammar = \$this->getGrammar();");
        $this->writeln();
        $this->writeln("return \$grammar->alt()");
        $this->indent();
        
        $choices = $alt["choices"];
        $numChoices = count($choices);
        for ($i=0; $i < $numChoices; $i++) {
            $elemRef = $this->elementRef($choices[$i]);
            $line = "->add({$elemRef})";
            if ($i == $numChoices - 1) {
                $line .= ";";
            }
            $this->writeln($line);
        }
        $this->dedent();
        $this->dedent();
        $this->writeln("}");

    }

    private function genSequences()
    {
        $seqs = $this->content["sequences"];
        foreach ($seqs as $seq) {
            $this->genSequence($seq);
            $this->writeln();
        }
    }

    private function genSequence($seq)
    {
        $name = $seq["name"];
        $this->writeln("private function {$name}()");
        $this->writeln("{");
        $this->indent();
        $this->writeln("\$grammar = \$this->getGrammar();");
        $this->writeln();
        $this->writeln("return \$grammar->seq()");
        $this->indent();

        $elems = $seq["elements"];
        $numElems = count($elems);
        for ($i=0; $i < $numElems; $i++) {
            $elemRef = $this->elementRef($elems[$i]);
            $line = "->add({$elemRef})";
            if ($i == $numElems - 1) {
                $line .= ";";
            }
            $this->writeln($line);
        }
        $this->dedent();
        $this->dedent();
        $this->writeln("}");

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
