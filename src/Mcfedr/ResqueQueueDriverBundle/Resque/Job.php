<?php

namespace Mcfedr\ResqueQueueDriverBundle\Resque;

use Mcfedr\QueueManagerBundle\Exception\UnexpectedJobDataException;
use Mcfedr\ResqueQueueDriverBundle\Queue\ResqueJob;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;

class Job
{
    /**
     * @var array This is where Resque injects the arguments
     */
    public $args;

    /**
     * @var string This is where Resque injects the current queue name
     */
    public $queue;

    /**
     * @var \Resque_Job This is where Resque injects its Job
     */
    public $job;

    /**
     * @throws UnexpectedJobDataException if resque message missing data fields name, arguments, kernel_options or environment variables are missing
     */
    public function perform()
    {
        if (!$this->isJobConfigured() || !$this->isKernelClassConfigured() || !$this->isKernelEnvironmentConfigured() || !$this->isKernelDebugConfigured()) {
            throw new UnexpectedJobDataException('Resque message missing data fields name, arguments, kernel_options or environment variables are missing');
        }

        $this->getContainer()->get('mcfedr_queue_manager.job_executor')->executeJob(new ResqueJob($this->args, null, null, $this->queue, static::class, null));
    }

    /**
     * @var KernelInterface
     */
    private $kernel = null;

    /**
     * @return bool
     */
    private function isJobConfigured()
    {
        return is_array($this->args) && isset($this->args['name']) && isset($this->args['arguments']);
    }

    /**
     * @return bool
     */
    private function isKernelClassConfigured()
    {
        return strlen(getenv('KERNEL_CLASS')) || isset($this->args['kernel_options']['kernel.root_dir']);
    }

    /**
     * @return bool
     */
    private function isKernelEnvironmentConfigured()
    {
        return strlen(getenv('SYMFONY_ENV')) || strlen(getenv('APP_ENV')) || isset($this->args['kernel_options']['kernel.environment']);
    }

    /**
     * @return bool
     */
    private function isKernelDebugConfigured()
    {
        return strlen(getenv('SYMFONY_DEBUG')) || strlen(getenv('APP_DEBUG')) || isset($this->args['kernel_options']['kernel.debug']);
    }

    /**
     * @return ContainerInterface
     */
    private function getContainer()
    {
        if (null === $this->kernel) {
            $this->kernel = $this->createKernel();
            $this->kernel->boot();
        }

        return $this->kernel->getContainer();
    }

    /**
     * @return KernelInterface
     */
    private function createKernel()
    {
        $kernelClass = $this->getKernelClass();

        return new $kernelClass($this->getKernelEnvironment(), $this->getKernelDebug());
    }

    public function tearDown()
    {
        if ($this->kernel) {
            $this->kernel->shutdown();
        }
    }

    /**
     * This is largely copied from how the test client finds and setups a kernel.
     *
     * @return string
     */
    private function getKernelClass()
    {
        $env = getenv('KERNEL_CLASS');
        if (strlen($env)) {
            return $env;
        }

        $iterator = (new Finder())
            ->name('*Kernel.php')
            ->depth(0)
            ->in(__DIR__.'/'.$this->args['kernel_options']['kernel.root_dir'])
            ->getIterator();
        $iterator->rewind(); //Seems weird that I have rewind a new iterator, but I do
        /** @var SplFileInfo $file */
        $file = $iterator->current();
        $class = $file->getBasename('.php');

        require_once $file;

        return $class;
    }

    /**
     * @return string
     */
    private function getKernelEnvironment()
    {
        $env = getenv('SYMFONY_ENV');
        if (strlen($env)) {
            return $env;
        }

        $env = getenv('APP_ENV');
        if (strlen($env)) {
            return $env;
        }

        return isset($this->args['kernel_options']['kernel.environment']) ? $this->args['kernel_options']['kernel.environment'] : 'dev';
    }

    /**
     * @return bool
     */
    private function getKernelDebug()
    {
        $debug = getenv('SYMFONY_DEBUG');
        if (strlen($debug)) {
            return '1' === $debug;
        }

        $debug = getenv('APP_DEBUG');
        if (strlen($debug)) {
            return '1' === $debug;
        }

        return isset($this->args['kernel_options']['kernel.debug']) ? $this->args['kernel_options']['kernel.debug'] : true;
    }
}
