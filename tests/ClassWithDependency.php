<?php

declare(strict_types=1);

namespace PiotrekR\Container\Tests;

use stdClass;

class ClassWithDependency
{
    public function __construct(public stdClass $dependency)
    {
    }
}
