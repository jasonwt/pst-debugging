<?php

/*TDD*/

declare(strict_types=1);

require_once(__DIR__ . "/../../vendor/autoload.php");

use PST\Debugging\TypeDump;
use PST\Testing\Exceptions\ShouldException;
use PST\Testing\Should;

use function PST\Debugging\dp;
use function PST\Debugging\addBorderToString;
use function PST\Debugging\dump;

try {
    Should::beAClass('PST\Debugging\TypeDump');

    Should::haveMethod(
        'PST\Debugging\TypeDump',

        'typeName',
        'indentString',
        'typeDump',
        'toString',
        'setIndentFunction',
        "registerTypeDump",
        "typeDumps",
        "unregisterTypeDump",
    );

    interface Interface1 {

    }

    interface Interface2 {

    }

    class ParentClass {
        public int $parentInt = 1;
        protected float $parentFloat = 1.1;
        private string $parentString = "parent string";
        public bool $parentBool = true;
        protected ?string $parentNull = null;
    }

    class TestClass extends ParentClass implements Interface1, Interface2 {
        private int $privateInt = 1;
    
        protected float $protectedFloat = 1.1;
        public array $publicArray = [
            "zero",
            "one",
            "two",
            "three",
            "int" => 1,
            "float" => 1.1,
            "bool" => true,
            "null" => null,
            "array" => [
                "zero",
                "one",
                "two",
                "three",
                "int" => 1,
                "float" => 1.1,
                "bool" => true,
                "null" => null,
        
            ]
    
        ];

        private string $privateString = "private string";
        protected bool $protectedBool = true;
        public ?string $publicNull = null;
    }

    $test = new TestClass();

//    echo dump($test);
    

} catch (ShouldException $e) {
    dp($e->getMessage());

} catch (\Throwable $e) {
    throw $e;
}