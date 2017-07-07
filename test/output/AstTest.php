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

namespace tbollmeier\parsian\output;


class AstTest extends \PHPUnit_Framework_TestCase
{
    private $ast;

    public function setUp()
    {
        $this->ast = new Ast("root");
        $this->ast->addChild(new Ast("child-1", "one"));
        $this->ast->addChild(new Ast("child-2", "two"));
    }

    public function testReplace()
    {
        echo $this->ast->toXml();

        $this->ast->replaceChild($this->ast->getChildren()[1], new Ast("new-child", "three"));

        echo $this->ast->toXml();

        $this->ast->replaceChild($this->ast->getChildren()[1]);

        echo $this->ast->toXml();
    }

}
