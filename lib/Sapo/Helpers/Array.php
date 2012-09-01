<?php

class Sapo_Helpers_Array
{
	
	public static function FilterRecursive(Array $source, $fn)
    {
        $result = array();
        foreach ($source as $key => $value)
        {
            if (is_array($value))
            {
                $result[$key] = self::FilterRecursive($value, $fn);
                continue;
            }
            if ($fn($key, $value))
            {
                $result[$key] = $value; // KEEP
                continue;
            }
        }
        return $result;
    }
}