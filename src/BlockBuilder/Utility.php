<?php namespace BlockBuilder;

defined('C5_EXECUTE') or die('Access Denied.');

class Utility
{

    public static function tab($numberOfTabs = 1) {

        $spaces = '';
        for ($i = 1; $i <= $numberOfTabs; $i++) {
            $spaces .= '    ';
        }

        return $spaces;

    }

    public static function arrayGap($maxKeyLength, $keyLength = 0) {

        $spaces = '';
        $numberOfSpaces = $maxKeyLength-$keyLength;
        for ($i = 1; $i <= $numberOfSpaces; $i++) {
            $spaces .= ' ';
        }

        return $spaces;

    }

    public static function convertHandleToNamespace($handle) {

        $handleParts = explode('_', $handle);

        if (is_array($handleParts)) {

            $namespace = '';

            foreach ($handleParts as $handlePart) {
                $namespace .= ucfirst($handlePart);
            }

        } else {
            $namespace = ucfirst($handle);
        }

        return $namespace;

    }

    public static function convertHandleToDashed($handle) {

        $handle = str_replace('_', '-', $handle);

        return $handle;

    }

}
