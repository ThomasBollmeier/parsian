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

class TransformationGenerator
{
    private $ruleName;
    private $transNode;
    private $varCounter;

    public function __construct(string $ruleName, Ast $transNode)
    {
        $this->ruleName = $ruleName;
        $this->transNode = $transNode;
    }

    public function genTransformation(Page $page)
    {
        $page->writeln("\$grammar->setCustomRuleAst(\"{$this->ruleName}\", " .
            "function (Ast \$ast) {");
        $page->indent();
        $this->genBody($page);
        $page->dedent();
        $page->writeln("});");
    }

    private function genBody(Page $page)
    {
        if (!$this->transNode->hasAttr('use-child')) {

            $this->varCounter = 0;
            $this->genNewNode("\$res", $this->transNode, $page);
            $page->writeln("return \$res;");

        } else {
            $page->writeln("\$child = \$ast->getChildren()[0];");
            $page->writeln("\$child->clearId();");
            $page->writeln("return \$child;");
        }
    }

    private function newLocal()
    {
        $this->varCounter++;
        return "\$local_{$this->varCounter}";

    }

    private function genNewNode($varName, Ast $node, Page $page) {

        $nameNode = $node->getChildrenByName("name");
        $nameNode = $nameNode[0];
        $nameNode = $nameNode->getChildren()[0];
        $nameExpr = $this->getSimpleVarExpr($nameNode);

        $textNode = $node->getChildrenByName("text");
        if (!empty($textNode)) {
            $textNode = $textNode[0];
            $textNode = $textNode->getChildren()[0];
            $textExpr = $this->getSimpleVarExpr($textNode);
        } else {
            $textExpr = "\"\"";
        }

        $page->writeln("$varName = new Ast($nameExpr, $textExpr);");

        $attrsNode = $node->getChildrenByName("attrs");
        if (!empty($attrsNode)) {
            $attrsNode = $attrsNode[0];
            foreach ($attrsNode->getChildren() as $attrNode) {
                $children = $attrNode->getChildren();
                $key = $children[0]->getText();
                $val = $children[1]->getText();
                $page->writeln("{$varName}->setAttr($key, $val);");
            }
        }

        $childrenNode = $node->getChildrenByName("children");
        if (!empty($childrenNode)) {
            $childrenNode = $childrenNode[0];
            $content = $childrenNode->getChildren()[0];
            switch ($content->getName()) {
                case "idref":
                    $id = $content->getText();
                    $local = $this->newLocal();
                    $page->writeln("foreach (\$ast->getChildrenById(\"$id\") as $local) {");
                    $page->indent();
                    $page->writeln("{$local}->clearId();");
                    $page->writeln("{$varName}->addChild($local);");
                    $page->dedent();
                    $page->writeln("}");
                    break;
                case "nameref":
                    $name = $content->getText();
                    $local = $this->newLocal();
                    $page->writeln("foreach (\$ast->getChildrenByName(\"$name\") as $local) {");
                    $page->indent();
                    $page->writeln("{$local}->clearId();");
                    $page->writeln("{$varName}->addChild($local);");
                    $page->dedent();
                    $page->writeln("}");
                    break;
                case "nodes":
                    foreach ($content->getChildren() as $childNode) {
                        $local = $this->newLocal();
                        switch($childNode->getName()) {
                            case "idref":
                                $id = $childNode->getText();
                                $line = "$local = \$ast->getChildrenById(\"{$id}\")[0];";
                                $page->writeln($line);
                                $page->writeln("{$local}->clearId();");
                                break;
                            case "nameref":
                                $name = $childNode->getText();
                                $line = "$local = \$ast->getChildrenByName(\"{$name}\")[0];";
                                $page->writeln($line);
                                $page->writeln("{$local}->clearId();");
                                break;
                            case "node":
                                $this->genNewNode($local, $childNode, $page);
                                break;
                        }
                        $page->writeln("{$varName}->addChild({$local});");
                    }
                    break;
            }
        }

    }

    private function getSimpleVarExpr(Ast $simple)
    {
        switch ($simple->getName()) {
            case "text":
                return $simple->getText();
            case "member":
                return $this->memberExpr($simple);
            default:
                return "";
        }
    }

    private function memberExpr(Ast $member)
    {
        $children = $member->getChildren();

        $obj = $children[0];
        switch ($obj->getName()) {
            case "idref":
                $id = $obj->getText();
                $member = "\$ast->getChildrenById(\"{$id}\")[0]";
                break;
            case "nameref":
                $name = $obj->getText();
                $member = "\$ast->getChildrenByName(\"{$name}\")[0]";
                break;
            case "child":
                $member = "\$ast->getChildren()[0]";
                break;
            default:
                return "";
        }

        $field = $children[1]->getText();
        switch ($field) {
            case "name":
                return "{$member}->getName()";
            case "text":
                return "{$member}->getText()";
        }

    }

}