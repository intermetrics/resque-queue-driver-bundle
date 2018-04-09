<?php

namespace Mcfedr\ResqueQueueDriverBundle\Manager;

use Mcfedr\QueueManagerBundle\Exception\NoSuchJobException;
use Mcfedr\QueueManagerBundle\Exception\WrongJobException;
use Mcfedr\QueueManagerBundle\Manager\QueueManager;
use Mcfedr\QueueManagerBundle\Queue\Job;
use Mcfedr\ResqueQueueDriverBundle\Queue\ResqueJob;
use Symfony\Component\Filesystem\Filesystem;

class ResqueQueueManager implements QueueManager
{
    /**
     * @var array
     */
    private $kernelOptions;

    /**
     * @var string
     */
    private $defaultQueue;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var bool
     */
    private $trackStatus;

    /**
     * @var string
     */
    private $jobClass;

    public function __construct(array $options)
    {
        $this->defaultQueue = $options['default_queue'];
        $this->setKernelOptions($options['kernel_options']);
        $this->debug = $options['debug'];
        if (!$this->debug) {
            \Resque::setBackend("{$options['host']}:{$options['port']}");
            if (isset($options['prefix'])) {
                \Resque_Redis::prefix($options['prefix']);
            }
        }
        $this->trackStatus = $options['track_status'];
        $this->jobClass = $options['job_class'] ?? \Mcfedr\ResqueQueueDriverBundle\Resque\Job::class;
    }

    private function setKernelOptions(array $kernelOptions)
    {
        $this->kernelOptions = $kernelOptions;

        //Convert root_dir to be relative to the resque bundle paths, this makes it possible to deploy workers in different places
        if (array_key_exists('kernel.root_dir', $this->kernelOptions)) {
            $this->kernelOptions['kernel.root_dir'] = (new Filesystem())
                ->makePathRelative($this->kernelOptions['kernel.root_dir'], __DIR__);
        }
    }

    public function put($name, array $arguments = [], array $options = [])
    {
        $queue = isset($options['queue']) ? $options['queue'] : $this->defaultQueue;
        $jobClass = isset($options['job_class']) ? $options['job_class'] : $this->jobClass;

        $resqueArguments = [
            'name' => $name,
            'arguments' => $arguments,
            'kernel_options' => $this->kernelOptions,
        ];

        $trackJobStatus = isset($options['track_status']) ? $options['track_status'] : $this->trackStatus;

        $id = null;

        if (isset($options['time'])) {
            $when = $options['time'];
        } elseif (isset($options['delay'])) {
            $when = time() + $options['delay'];
        } else {
            $when = null;
        }

        if (!$this->debug) {
            if ($when) {
                \ResqueScheduler::enqueueAt($when, $queue, $jobClass, $resqueArguments, $trackJobStatus);
            } else {
                $id = \Resque::enqueue($queue, $jobClass, $resqueArguments, $trackJobStatus);
            }
        }

        return new ResqueJob($resqueArguments, $id, $when, $queue, $jobClass, $trackJobStatus);
    }

    public function delete(Job $job)
    {
        if (!$job instanceof ResqueJob) {
            throw new WrongJobException('Resque queue manager can only delete resque jobs');
        }

        if (!$job->isFutureJob()) {
            throw new NoSuchJobException('Resque queue manager can only delete future jobs');
        }

        if ($this->debug) {
            return;
        }

        if (\ResqueScheduler::removeDelayedJobFromTimestamp($job->getWhen(), $job->getQueue(), $job->getClass(), $job->getResqueArguments(), $job->isTrackStatus()) < 1) {
            throw new NoSuchJobException('No jobs were found');
        }
    }
}
