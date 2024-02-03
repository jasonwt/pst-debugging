<?php

/*TDD*/

declare(strict_types=1);

require_once(__DIR__ . "/../../vendor/autoload.php");

use PST\Debugging\TypeDump;
use PST\Testing\Exceptions\ShouldException;
use PST\Testing\Should;

use function PST\Debugging\dp;

try {
    
    

} catch (ShouldException $e) {
    dp($e->getMessage());

} catch (\Throwable $e) {
    throw $e;
}