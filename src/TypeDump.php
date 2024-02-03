<?php

declare(strict_types=1);

namespace PST\Debugging;

use Closure;
use InvalidArgumentException;
use ReflectionProperty;

/**
 * TypeDump
 * 
 * @package PST\Debugging
 * 
 */
abstract class TypeDump {
    private string $typeName = "";
    private static ?Closure $indentFunction = null;

    private static array $typeDumps = [];

    /**
     * Constructor
     * 
     * @param string $typeName 
     * 
     * @return void 
     * 
     * @throws InvalidArgumentException 
     * 
     */
    protected function __construct(string $typeName) {
        $this->typeName = $typeName;

        if (($this->typeName = trim($typeName)) === "")
            throw new InvalidArgumentException("The type name cannot be empty.");
    }

    /**
     * Returns the type name
     * 
     * @return string 
     * 
     */
    final public function typeName(): string {
        return $this->typeName;
    }

    /**
     * Returns a indented string
     * 
     * @param mixed $value 
     * @param int $recursionDepth 
     * 
     * @return string 
     * 
     */
    final public static function indentString(int $indentLevel): string {
        static::$indentFunction ??= fn($i) => str_repeat("    ", $i);
        
        return (self::$indentFunction)($indentLevel);
    }

    /**
     * Returns a string representation of a value
     * 
     * @param mixed $value 
     * @param int $recursionDepth 
     * 
     * @return string 
     * 
     */
    public function typeDump($value, int $recursionDepth): string {
        return TypeDump::toString($value, $recursionDepth);
    }

    /**
     * Returns a static string representation of a value
     * 
     * @param mixed $value 
     * @param int $recursionDepth 
     * 
     * @return string 
     * 
     */
    public static function toString($value, int $recursionDepth = 0): string {
        $valueType = gettype($value);

        if ($valueType !== 'object') {
            if (array_key_exists($valueType, self::$typeDumps))
                return self::$typeDumps[$valueType]->toString($value, $recursionDepth);

            switch ($valueType) {
                case 'string':
                    return TypeDump::indentString($recursionDepth) . "'" . $value . "'";
                case 'integer':
                    return TypeDump::indentString($recursionDepth) . sprintf("%d", $value);
                case 'double':
                    return TypeDump::indentString($recursionDepth) . sprintf("%f", $value);
                case 'boolean':
                    return TypeDump::indentString($recursionDepth) . ($value ? "TRUE" : "FALSE");
                case 'NULL':
                    return TypeDump::indentString($recursionDepth) . "NULL";
                case 'array':
                    $result = TypeDump::indentString($recursionDepth) . "ARRAY [\n";

                    if (count($value) > 0)
                        $maxKeyLength = max(array_map(fn($key) => strlen(static::toString($key)), array_keys($value))) + 2;
                    
                    $outputs = [];

                    foreach ($value as $key => $val) {
                        $keyValue = '[' . static::toString($key) . ']';
                        $keyValue = str_pad($keyValue, $maxKeyLength, " ", STR_PAD_RIGHT);

                        $outputs[] = TypeDump::indentString($recursionDepth + 1) . $keyValue . " => " . ltrim(self::toString($val, $recursionDepth + 1));
                    }

                    $result .= implode(",\n", array_map('rtrim', $outputs)) . "\n";

                    $result .= TypeDump::indentString($recursionDepth) . "]\n";
                    
                    return $result;
                case 'resource':
                    return TypeDump::indentString($recursionDepth) . "RESOURCE";
                case 'unknown type':
                    return "UNKNOWN TYPE";
                
                default:
                    return var_export($value, true);
            }
        }
            
        $getClass = get_class($value);

        foreach (self::$typeDumps as $type => $typeToString) {
            if ($getClass === $type)
                return $typeToString->toString($value, $recursionDepth);
        }

        foreach (self::$typeDumps as $type => $typeToString) {
            if ($value instanceof $type)
                return trim($typeToString->toString($value, $recursionDepth));
        }

        if (array_key_exists("object", self::$typeDumps))
            return self::$typeDumps["object"]->toString($value, $recursionDepth);

        $reflection = new \ReflectionObject($value);
        
        // get the class value extends from
        if (($parentClass = ($reflection->getParentClass())) !== false)
            $parentClass = "EXTENDS " . $parentClass->getName() . " ";

        // get the interfaces value implements
        if (($interfaces = implode(", ", $reflection->getInterfaceNames())) !== "")
            $interfaces = "IMPLEMENTS " . $interfaces . " ";

        $result = TypeDump::indentString($recursionDepth) . "OBJECT " . get_class($value) . " $parentClass$interfaces{\n";

        $protections = [ReflectionProperty::IS_PRIVATE, ReflectionProperty::IS_PROTECTED, ReflectionProperty::IS_PUBLIC];

        foreach ($protections as $protectionType) {
            $properties = $reflection->getProperties($protectionType);
            
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $propProtection = ($property->isPrivate() ? "private" : ($property->isProtected() ? "protected" : "public"));
                $propName = $property->getName();
                $propType = $property->getType() ?? gettype($property->getValue($value));
                $propValue = $property->getValue($value);

                $key = strtoupper($propProtection . " " . $propType) . " $" . $propName;

                $result .= TypeDump::indentString($recursionDepth + 1) . 
                    $key . " = " . trim(self::toString($propValue,  $recursionDepth + 1)) . ";\n";
            }

            $result .= "\n";
        }

        $result = rtrim($result) . "\n";

        $result .= TypeDump::indentString($recursionDepth) . "}\n";
        
        return $result;
    }

    /**
     * Sets the indent function
     * 
     * @param Closure $indentFunction 
     * 
     * @return void 
     * 
     */
    final public static function setIndentFunction(Closure $indentFunction): void {
        self::$indentFunction = $indentFunction;
    }

    /**
     * Registers a type dump
     * 
     * @param TypeDump $typeToString 
     * 
     * @return void 
     * 
     */
    public static function registerTypeDump(TypeDump $typeToString): void {
        self::$typeDumps[$typeToString->typeName()] = $typeToString;
    }

    /**
     * Returns the type dumps
     * 
     * @return array 
     * 
     */
    public static function typeDumps(): array {
        return self::$typeDumps;
    }

    /**
     * Unregisters a type dump
     * 
     * @param TypeDump $typeToString 
     * 
     * @return void 
     * 
     */
    public static function unregisterTypeDump(TypeDump $typeToString): void {
        unset(self::$typeDumps[$typeToString->typeName()]);
    }
}