<?php

namespace FlyPHP\Util;

/**
 * A safe implementation of array_search.
 */
class ArraySearch
{
    /**
     * Searches the array for a given value and returns the corresponding key if successful.
     *
     * @param mixed $needle
     * @param array $haystack
     * @return mixed FALSE if missing, or ARRAY KEY if located.
     */
    public static function findObject($needle, array $haystack)
    {
        foreach ($haystack as $key => $item) {
            if ($item === $needle) {
                return $key;
            }
        }

        return false;
    }
}