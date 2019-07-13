<?php


namespace torrentupload;


class utils
{

    public static function utf8_decode_array($array)
    {
        foreach ($array as $key => $value) {
            if (is_string($value))
                $array[$key] = utf8_decode($value);
        }
        return $array;
    }
}