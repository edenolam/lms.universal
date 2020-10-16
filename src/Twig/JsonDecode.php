<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class JsonDecode
 */
class JsonDecode extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('json_decode', [$this, 'json_decode']),
        ];
    }

    public function json_decode($string)
    {
        return json_decode($string);
    }
}
