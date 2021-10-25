<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToPromiseMapperRenamedFieldsTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BelongsToPromiseMapperRenamedFieldsTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}