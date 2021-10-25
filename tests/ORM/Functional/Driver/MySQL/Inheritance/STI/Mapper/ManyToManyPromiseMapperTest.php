<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Inheritance\STI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\Mapper\ManyToManyPromiseMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyPromiseMapperTest extends CommonTest
{
    public const DRIVER = 'mysql';
}