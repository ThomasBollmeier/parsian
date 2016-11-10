<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/10/16
 * Time: 6:59 PM
 */

namespace tbollmeier\parsian;


interface CharInput
{

    function open();

    function close();

    function hasMoreChars() : bool;

    function nextChar() : string;

    function line() : int;

    function column() : int;

}