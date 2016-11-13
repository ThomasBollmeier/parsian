<?php
/**
 * Created by PhpStorm.
 * User: drbolle
 * Date: 11/13/16
 * Time: 11:36 AM
 */

namespace tbollmeier\parsian;


class TokenInputImpl implements TokenInput
{
    private $charIn;
    private $tokens;
    private $charInfoBuf;

    public function __construct(CharInput $charIn, $config)
    {
        $this->charIn = $charIn;
    }

    function open()
    {
        // TODO: Implement open() method.
    }

    function close()
    {
        // TODO: Implement close() method.
    }

    function hasMoreTokens() : bool
    {
        // TODO: Implement hasMoreTokens() method.
    }

    function nextToken() : Token
    {
        // TODO: Implement nextToken() method.
    }
}