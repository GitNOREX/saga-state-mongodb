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

use Broadway\Saga\State\RepositoryInterface;
use Broadway\Saga\State\Testing\AbstractRepositoryTest;
use MongoDB\Client;

class MongoDBRepositoryTest extends AbstractRepositoryTest
{
    protected static $dbName = 'doctrine_mongodb';
    protected Client $client;

    protected function createRepository(): RepositoryInterface
    {
        $this->client = new Client(
            'mongodb://mongodb/'
        );

        $db = $this->client->selectDatabase(self::$dbName);
        $db->dropCollection('test');
        $db->createCollection('test');
        return new MongoDBRepository($this->client->selectCollection(self::$dbName, 'test'));
    }

    public function tearDown(): void
    {
        $collection = $this->client->selectDatabase(self::$dbName)->selectCollection('test');
        $collection->drop();
        unset($this->client);
    }
}
