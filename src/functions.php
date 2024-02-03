<?php

declare(strict_types=1);

namespace PST\Debugging;

/**
 * Adds a border and optional title to a string
 * 
 * @param string $string 
 * @param string $title 
 * 
 * @return string 
 * 
 */
function addBorderToString(string $string, string $title = ""): string {
    $output = "\n";

    $lines = explode("\n", $string);
    array_unshift($lines, "");

    $width = (int) max(strlen($title = trim($title)), max(array_map(fn($line) => strlen($line), $lines))) + 4;

    if ($title !== "")
        $output .= "+" . str_pad(" " . $title . " ", $width, "-", STR_PAD_BOTH) . "+\n";    
    else
        $output .= "\n+" . str_repeat("-", $width) . "+\n";
    
    foreach ($lines as $line)
        $output .= "|  " . str_pad($line, $width - 4 ) . "  |\n";

    $output .= "+" . str_repeat("-", $width) . "+\n";

    return $output . "\n";
}

/**
 * Prints or returns a string representation of a value
 * 
 * @param mixed $value 
 * @param bool $returnDumpString 
 * @param bool $includeBacktrace 
 * @param bool $includeBorder 
 * 
 * @return void|string 
 * 
 */
function dump($value, bool $returnDumpString = false, bool $includeBacktrace = false, bool $includeBorder = true) {
    $debugBacktrace = array_values(array_filter(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), function($item) {
       return substr($item["file"], -27) !== "debugging/src/functions.php";
    }));
    
    $valueDump = TypeDump::toString($value);

    if ($includeBacktrace) {
        $debugBacktraceArray = array_map(fn($item) => rtrim($item["file"] . ":" . $item["line"]), $debugBacktrace);

        $valueDump .= "\nBACKTRACE:\n" . implode("\n", $debugBacktraceArray) . "\n";
    }

    if ($includeBorder) {
        $title = $debugBacktrace[0]["file"] . ":" . $debugBacktrace[0]["line"];

        $valueDump = addBorderToString($valueDump, $title);
    }

    if ($returnDumpString)
        return $valueDump;

    echo $valueDump;
}

/**
 * Prints a string representation of a value
 * 
 * @param mixed $value 
 * 
 * @return void 
 * 
 */
function dp(... $items): void {
    echo dump($items, true, false, true);
}