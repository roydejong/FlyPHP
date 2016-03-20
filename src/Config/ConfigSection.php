<?php

namespace FlyPHP\Config;

/**
 * A configuration file section.
 */
abstract class ConfigSection
{
    /**
     * Given an array of $data, fills this section with values, overriding any defaults or previously set data.
     *
     * @param array $data
     */
    public function fill(array $data)
    {
        foreach ($data as $key => $value)
        {
            if (isset($this->$key))
            {
                $this->$key = $value;
            }
        }
    }
}