<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Heap;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\Comment;
use Spiral\ORM\Tests\Fixtures\EntityMapper;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\UnitOfWork;

abstract class RefersToRelationTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'         => 'primary',
            'email'      => 'string',
            'balance'    => 'float',
            'comment_id' => 'integer,nullable'
        ]);

        $this->makeTable('comment', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'message' => 'string'
        ], [
            'user_id' => ['table' => 'user', 'column' => 'id']
        ]);

        $this->orm = $this->orm->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance', 'comment_id'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'lastComment' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id'
                        ],
                    ],
                    'comments'    => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],

                ]
            ],
            Comment::class => [
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testCreateUserWithDoubleReference()
    {
        $u = new User();
        $u->email = "email@email.com";
        $u->balance = 100;

        $c = new Comment();
        $c->message = "last comment";

        $u->addComment($c);

        $tr = new UnitOfWork($this->orm);
        $tr->store($u);
        $tr->run();

        $s = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        $this->assertNotNull($u->lastComment);
        $this->assertSame($u->lastComment, $u->comments[0]);
    }

    public function testCreateWhenParentExists()
    {
        $u = new User();
        $u->email = "email@email.com";
        $u->balance = 100;

        $tr = new UnitOfWork($this->orm);
        $tr->store($u);
        $tr->run();

        $c = new Comment();
        $c->message = "last comment";

        $u->addComment($c);

        $tr = new UnitOfWork($this->orm);
        $tr->store($u);
        $tr->run();

        $s = new Selector($this->orm->withHeap(new Heap()), User::class);
        $u = $s->load('lastComment')->load('comments')->wherePK(1)->fetchOne();

        $this->assertNotNull($u->lastComment);
        $this->assertSame($u->lastComment, $u->comments[0]);
    }

    /**
     * @expectedException \Spiral\ORM\Exception\TransactionException
     */
    public function testCreateWithoutProperDependency()
    {
        $u = new User();
        $u->email = "email@email.com";
        $u->balance = 100;

        $c = new Comment();
        $c->message = "last comment";

        $u->lastComment = $c;

        $tr = new UnitOfWork($this->orm);
        $tr->store($u);
        $tr->run();
    }

    // todo: test when parent is defined

    // todo: set null
}