<?php
/**
 * Sets .env values at runtime.
 */

namespace App\Classes;

class Env {

    /**
     * Set the specified key to a new value.
     * Optionally, write the new key to the bottom of the .env file if not already present.
     *
     * @param  string $envKey
     * @param  string $envValue
     * @param  Boolean $addKeyIfMissing (optional)
     * @return void
     */
    public static function setValue($envKey, $envValue, $addKeyIfMissing = false)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        $envHasKey = strpos($str, "{$envKey}=") !== false;
        $oldValue = env($envKey);
        $possibleOldValues = ['"'.$oldValue.'"', $oldValue];
        if ($oldValue === null || !strlen($oldValue)) {
            array_unshift($possibleOldValues, "null");
        }
        $envValue = (string)$envValue;

        if (!strlen($envValue)) {
            $envValue = 'null';
        }

        // wrap the new value in quotes if it contains space character(s)
        if (strpos($envValue, ' ') !== false) {
            $envValue = '"' . $envValue . '"';
        }
        if ($envHasKey) {
            // perform a replacement for each possible permutation of the old value
            foreach ($possibleOldValues as $val) {
                if (strpos($str, "{$envKey}={$val}") !== false) {
                    $str = str_replace("{$envKey}={$val}", "{$envKey}={$envValue}", $str);
                    break;
                }
            }
        } elseif ($addKeyIfMissing) {
            // add the key:value pair to the end of the .env file
            $str .= "\n" . "{$envKey}={$envValue}";
        }

        $fp = fopen($envFile, 'w');
        fwrite($fp, $str);
        fclose($fp);
    }

}
