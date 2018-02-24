<?php

namespace Mcfedr\ResqueQueueDriverBundle\Tests\Resque\Job;

use Mcfedr\ResqueQueueDriverBundle\Resque\Job;
use Mcfedr\ResqueQueueDriverBundle\Worker\TestWorker;

/**
 * @backupGlobals enabled
 */
class JobTest extends \PHPUnit_Framework_TestCase
{
    public function testPerform()
    {
        $job = new Job();
        $job->args = [
            'name' => TestWorker::class,
            'arguments' => ['first' => 1, 'second' => 'second'],
            'kernel_options' => [
                'kernel.root_dir' => '../../../../tests/',
                'kernel.environment' => 'test',
                'kernel.debug' => true,
            ],
        ];
        $job->perform();
    }

    /**
     * @expectedException \Mcfedr\QueueManagerBundle\Exception\UnexpectedJobDataException
     * @expectedExceptionMessage kernel_options
     */
    public function testMissingKernelOptions()
    {
        $job = new Job();
        $job->args = [
            'name' => TestWorker::class,
            'arguments' => ['first' => 1, 'second' => 'second'],
        ];
        $job->perform();
    }

    public function testPerformWithSymfonyEnvironmentVariables()
    {
        putenv('KERNEL_CLASS=\TestKernel');
        putenv('SYMFONY_ENV=dev');
        putenv('SYMFONY_DEBUG=1');

        $job = new Job();
        $job->args = [
            'name' => TestWorker::class,
            'arguments' => ['first' => 1, 'second' => 'second'],
        ];
        $job->perform();
    }

    public function testPerformWithSymfonyFlexEnvironmentVariables()
    {
        putenv('KERNEL_CLASS=\TestKernel');
        putenv('APP_ENV=dev');
        putenv('APP_DEBUG=1');

        $job = new Job();
        $job->args = [
            'name' => TestWorker::class,
            'arguments' => ['first' => 1, 'second' => 'second'],
        ];
        $job->perform();
    }
}
