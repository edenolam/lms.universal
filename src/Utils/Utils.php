<?php

namespace App\Utils;

class Utils
{
    /**
     * [date_transformation description]
     * @param [type] $str [description]
     * @return [type] [description]
     */
    public static function date_transformation($str)
    {
        $date = substr($str, 6, 4) . '-' . substr($str, 3, 2) . '-' . substr($str, 0, 2);

        return \DateTime::createFromFormat('Y-m-d', $date);
    }

    /***
     * slug function
     */
    public static function slug(string $string, string $char): string
    {
        return preg_replace('/\s+/', $char, mb_strtolower(trim(strip_tags($string)), 'UTF-8'));
    }

    public function mbBasename($path)
    {
        if (preg_match('@^.*[\\\\/]([^\\\\/]+)$@s', $path, $matches)) {
            return $matches[1];
        } elseif (preg_match('@^([^\\\\/]+)$@s', $path, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
