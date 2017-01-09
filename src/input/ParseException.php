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


namespace tbollmeier\parsian\input;


class ParseException extends \Exception
{
    /** Error codes */
    const NO_TOKEN = 1;
    const UNEXPECTED_TOKEN = 2;
    const OTHER_ERROR = 99;

    /**
     * Create parse exceptiion for missing token
     *
     * @param $tokenTypes
     * @return ParseException
     */
    public static function noTokenForTokenTypes($tokenTypes) : ParseException
    {
        $message = "No token could be found for any of types ";
        $message .= join(", ", $tokenTypes);

        return new ParseException($message, self::NO_TOKEN);
    }

    /**
     * Create parse exceptiion for missing token
     *
     * @param $tokenType
     * @return ParseException
     */
    public static function noTokenForTokenType($tokenType) : ParseException
    {
        $message = "No token could be found for type {$tokenType}";

        return new ParseException($message, self::NO_TOKEN);
    }

    public static function unexpectedToken(Token $token) : ParseException
    {
        $text = $token->getContent();
        $pos = $token->getStartPos();
        $line = $pos->line;
        $col = $pos->column;
        $message = "Unexpected token '{$text}' at line {$line}, column {$col}";

        return new ParseException($message, self::UNEXPECTED_TOKEN);
    }

    public static function createError($message) : ParseException
    {
        return new ParseException($message, self::OTHER_ERROR);
    }

}