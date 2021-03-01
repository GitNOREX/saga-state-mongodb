<?php

declare(strict_types=1);

/*
 * This file is part of the broadway/saga-state-mongodb package.
 *
 * (c) 2020 Broadway project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Broadway\Saga\State\MongoDB;

use Broadway\Saga\State;
use Broadway\Saga\State\Criteria;
use Broadway\Saga\State\RepositoryException;
use Broadway\Saga\State\RepositoryInterface;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;

class MongoDBRepository implements RepositoryInterface
{
    private Collection $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(Criteria $criteria, $sagaId): ?State
    {
        $cursor = $this->createQuery($criteria, $sagaId);
        $results = $cursor->toArray();

        $count = count($results);
        if (1 === $count) {
            return State::deserialize(array_shift($results));
        }

        if ($count > 1) {
            throw new RepositoryException('Multiple saga state instances found.');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(State $state, $sagaId): void
    {
        $serializedState = $state->serialize();
        $serializedState['_id'] = $serializedState['id'];
        $serializedState['sagaId'] = $sagaId;
        $serializedState['removed'] = $state->isDone();

        $this->collection->updateOne(
            ['_id' => $serializedState['id']],
            ['$set' => $serializedState],
            ['upsert' => true]
        );
    }

    private function createQuery(Criteria $criteria, $sagaId): Cursor
    {
        $comparisons = $criteria->getComparisons();
        $filters = ['removed' => false, 'sagaId' => $sagaId];
        foreach ($comparisons as $key => $value) {
            $filters["values.{$key}"] = $value;
        }
        return $this->collection->find($filters, ['typeMap' => ['root' => 'array', 'document' => 'array']]);
    }
}
