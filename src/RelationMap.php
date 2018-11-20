<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\ChainCommand;
use Spiral\ORM\Command\ContextCommandInterface;

final class RelationMap
{
    /**
     * @invisible
     * @var ORMInterface
     */
    private $orm;

    /** @var RelationInterface[] */
    private $relations = [];

    public function __construct(ORMInterface $orm, array $relations)
    {
        $this->orm = $orm;
        $this->relations = $relations;
    }

    public function init(State $state, array $data): array
    {
        // easy to read huh?
        foreach ($this->relations as $name => $relation) {
            if (array_key_exists($name, $data)) {
                $item = $data[$name];

                if (is_object($item) || is_null($item)) {
                    $state->setRelation($name, $item);
                    continue;
                }

                if (!$relation->isCollection()) {
                    $data[$name] = $relation->init($item);
                    $state->setRelation($name, $data[$name]);
                    continue;
                }

                $relData = $relation->initArray($item);

                $state->setRelation($name, $relData);
                $data[$name] = $relation->wrapCollection($relData);
            }
        }

        return $data;
    }

    public function queueRelations($entity, ContextCommandInterface $command): ContextCommandInterface
    {
        // todo: what if entity new?
        $state = $this->orm->getHeap()->get($entity);

        $chain = new ChainCommand();

        $data = $this->orm->getMapper($entity)->extract($entity);

        foreach ($this->relations as $name => $relation) {
            if ($relation->isCascade() && $relation->isLeading()) {
                if ($state->getRefMap($name)) {
                    continue;
                }
                $state->setRefMap($name, true);

                $chain->addCommand($relation->queueChange(
                    $entity,
                    $state,
                    $data[$name] ?? null,
                    $state->getRelation($name),
                    $command
                ));
            }
        }

        $chain->addTargetCommand($command);

        foreach ($this->relations as $name => $relation) {
            if ($relation->isCascade() && !$relation->isLeading()) {
                if ($state->getRefMap($name)) {
                    continue;
                }

                $state->setRefMap($name, true);
                $chain->addCommand($relation->queueChange(
                    $entity,
                    $state,
                    $data[$name] ?? null,
                    $state->getRelation($name),
                    $command
                ));
            }
        }

        $chain->onComplete(function () use ($state) {
            foreach ($this->relations as $name => $relation) {
                $state->setRefMap($name, false);
            }
        });

        return $chain;
    }
}