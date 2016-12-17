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

namespace tbollmeier\parsian;


class Ast
{
    private $name;
    private $text;
    private $parent;
    private $children;
    private $attrs;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text)
    {
        $this->text = $text;
    }

    /**
     * @return null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return array
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    public function getAttr($key) : string
    {
        return $this->attrs[$key];
    }

    /**
     * @param array $attrs
     */
    public function setAttr(string $key, string $value)
    {
        $this->attrs[$key] = $value;
    }


    public function __construct(string $name, string $text="")
    {
        $this->name = $name;
        $this->text = $text;
        $this->parent = null;
        $this->children = [];
        $this->attrs = [];
    }

    public function addChild(Ast $child)
    {
        $this->children[] = $child;
        $child->parent = $this;
    }

    public function toXml()
    {
        $xml = "<{$this->name}";

        foreach ($this->attrs as $key => $value) {
            $xml .= " $key=\"$value\"";
        }

        if (empty($this->text) && empty($this->children)) {
            $xml .= " />";
            return $xml;
        }

        $xml .= ">";

        if (!empty($this->text)) {
            $xml .= $this->text;
        }

        foreach ($this->children as $child) {
            $xml .= $child->toXml();
        }

        $xml .= "</{$this->name}>";

        return $xml;
    }

}