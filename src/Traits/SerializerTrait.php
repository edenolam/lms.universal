<?php

namespace App\Traits;

use App\Utils\ArrayUtils;

/**
 * SerializerTrait
 *
 * @author Free
 */
trait SerializerTrait
{
    /**
     * SetIfPropertyExists.
     * Sets an entity prop from an array data source.
     *
     * @param $prop   - the property path
     * @param $setter - the setter to use
     * @param $data   - the data array
     * @param $object - the object to use the setter on
     */
    public function sipe($prop, $setter, $data = [], $object, $trim = true)
    {
        if ($data && is_array($data)) {
            try {
                $value = ArrayUtils::get($data, $prop);

                if (is_string($value) && $trim) {
                    $value = trim($value);
                }

                $object->{$setter}($value);
            } catch (\Exception $e) {
            }
        }
    }
}
