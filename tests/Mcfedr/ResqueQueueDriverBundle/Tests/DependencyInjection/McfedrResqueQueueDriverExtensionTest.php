<?php

namespace Mcfedr\ResqueQueueDriverBundle\Tests\DependencyInjection;

use Mcfedr\ResqueQueueDriverBundle\Manager\ResqueQueueManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class McfedrResqueQueueDriverExtensionTest extends WebTestCase
{
    public function testConfiguration()
    {
        $client = static::createClient();
        $this->assertTrue($client->getContainer()->has(ResqueQueueManager::class), 'service for resque queue manager exists');
        $this->assertTrue($client->getContainer()->has('mcfedr_queue_manager.default'), 'default manager exists');
    }
}
