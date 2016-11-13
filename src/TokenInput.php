<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/13/16
 * Time: 11:16 AM
 */

namespace tbollmeier\parsian;


interface TokenInput
{
    function open();

    function close();

    function hasMoreTokens() : bool;

    function nextToken() : Token;

}