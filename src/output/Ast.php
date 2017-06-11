<?php
/*
Copyright 2016-2017 Thomas Bollmeier <entwickler@tbollmeier.de>

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

namespace tbollmeier\parsian\output;


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

    public function getChildrenById($id) : array
    {
        $res = [];
        foreach ($this->children as $child) {
            if ($child->hasAttr("id") && $child->getAttr("id") == $id) {
                $res[] = $child;
            }
        }

        return $res;
    }

    public function setId($id)
    {
        $this->setAttr('id', $id);
    }

    public function clearId()
    {
        if (array_key_exists("id", $this->attrs)) {
            unset($this->attrs["id"]);
        }
    }

    public function getId()
    {
        return array_key_exists('id', $this->attrs) ?
            $this->attrs['id'] : "";
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

    public function hasAttr($key) : bool
    {
        return array_key_exists($key, $this->attrs);
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

    public function accept(Visitor $visitor)
    {
        $stopped = $visitor->enter($this);
        if ($stopped === true) return $stopped;

        foreach ($this->children as $child) {
            $stopped = $child->accept($visitor);
            if ($stopped === true) return $stopped;
        }

        $visitor->leave($this);

        return false; // false = no stop requested
    }

    public function toXml($indentSize=2)
    {
        $formatter = new XMLFormatter($indentSize);
        $this->accept($formatter);

        return $formatter->getXml();
    }

}