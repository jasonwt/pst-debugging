<?php

declare(strict_types=1);

namespace PST\Debugging;

use ReflectionProperty;

/**
 * Debugging
 * 
 * @package PST\Debugging
 * 
 */
class Debugging {
    private function __construct() {}

    /**
     * Returns a string representation of a value
     * 
     * @param mixed $value 
     * @param int $indentLevel 
     * 
     * @return string 
     * 
     */
    public static function toString($value, int $indentLevel = 0): string {
        $indentFunc = fn($level) => str_repeat("     ", $level);

        $valueType = gettype($value);

        $output = $indentFunc($indentLevel);
        
        if ($valueType === "boolean") {
            $output .= $value ? "TRUE" : "FALSE";
        } else if ($valueType === "NULL") {
            $output .= "NULL";
        } else if ($valueType === "array") {
            $output .= "array [\n";

            if (count($value) > 0) {
                $maxKeyLength = max(array_map(fn($key) => strlen(self::toString($key)), array_keys($value)));

                $outputs = array_reduce(array_keys($value), function($carry, $key) use ($value, $indentFunc, $indentLevel, $maxKeyLength) {
                    $carry[] = (is_object($value[$key]) || is_array($value[$key]) ? "\n" : "") . 
                        $indentFunc($indentLevel+1) . 
                        str_pad("[" . self::toString($key) . "]", $maxKeyLength + 2) . 
                        " => " . 
                        ltrim(self::toString($value[$key], $indentLevel+1));

                    return $carry;
                }, []);

                $output .= implode(",\n", $outputs) . "\n";
            }

            $output .= $indentFunc($indentLevel) . "]";
        } else if ($valueType === "object") {
            $output .= "class " . get_class($value) . " {\n";

            $reflection = new \ReflectionObject($value);

            $protections = [ReflectionProperty::IS_PRIVATE, ReflectionProperty::IS_PROTECTED, ReflectionProperty::IS_PUBLIC];

            foreach ($protections as $protectionType) {
                $properties = $reflection->getProperties($protectionType);
                
                foreach ($properties as $property) {
                    $property->setAccessible(true);
                    $propProtection = ($property->isPrivate() ? "private" : ($property->isProtected() ? "protected" : "public"));
                    $propName = $property->getName();
                    $propType = $property->getType() ?? gettype($property->getValue($value));
                    $propValue = $property->getValue($value);

                    $key = $propProtection . " " . $propType . " \$" . $propName;

                    $output .= $indentFunc($indentLevel+1) . 
                        $key . " => " . ltrim(self::toString($propValue,  $indentLevel+1)) . ";\n\n";
                }
            }

            $output .= $indentFunc($indentLevel) . "}\n\n";
        } else if ($valueType === "string") {
            $output .= "'" . $value . "'";
        } else if ($valueType === "integer") {
            $output .= sprintf("%d", $value);
        } else if ($valueType === "double") {
            $output .= sprintf("%f", $value);
        } else {
            return print_r($value, true);
        }

        return rtrim($output);
    }

    /**
     * Returns a string representation of a value
     * 
     * @param mixed $value 
     * 
     * @return string 
     * 
     */
    protected static function ds(array $items): string {
        $debugBacktrace = array_map(fn($item) => rtrim($item["file"] . ":" . $item["line"]), debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        array_shift($debugBacktrace); // Remove this function (ds) from the backtrace (it's always the first item
        array_shift($debugBacktrace);

        $lines = [array_shift($debugBacktrace), ""];

        foreach ($items as $k => $item)
            $lines = array_merge($lines, array_map(fn($item) => rtrim($item), explode("\n", self::toString($item))));

        $lines = array_merge($lines, array_merge(["", "BACKTRACE:"], $debugBacktrace, [""]));

        $width = (int) ((max(array_map(fn($value) => strlen($value), $lines))) + 20);

        $output = "\n+" . str_pad(" " . array_shift($lines) . " ", $width, "-", STR_PAD_BOTH) . "+\n";

        foreach ($lines as $line)
            $output .= "|  " . str_pad($line, $width - 3, " ", STR_PAD_RIGHT) . " |\n";

        $output .= "+" . str_repeat("-", $width ) . "+\n\n";

        return $output;
    }

    /**
     * Prints a string representation of a value
     * 
     * @param mixed $value 
     * 
     * @return void 
     * 
     */

    public static function dp(... $items): void {
        echo self::ds($items);
    }
    
    /**
     * Prints a string representation of a value
     * 
     * @param mixed $value 
     * 
     * @return void 
     * 
     */
    public static function dpre(... $items): void {
        echo "<pre>" . self::ds($items) . "</pre>";
    }
}

// Path: debugging/src/Debugging.php
function dp(... $items): void {
    Debugging::dp(... $items);
}

// Path: debugging/src/Debugging.php
function dpre(... $items): void {
    Debugging::dpre(... $items);
}