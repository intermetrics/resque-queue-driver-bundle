# Resque Queue Driver Bundle

A driver for [Queue Manager Bundle](https://github.com/mcfedr/queue-manager-bundle) that uses resque

[![Latest Stable Version](https://poser.pugx.org/mcfedr/resque-queue-driver-bundle/v/stable.png)](https://packagist.org/packages/mcfedr/resque-queue-driver-bundle)
[![License](https://poser.pugx.org/mcfedr/resque-queue-driver-bundle/license.png)](https://packagist.org/packages/mcfedr/resque-queue-driver-bundle)
[![Build Status](https://travis-ci.org/mcfedr/resque-queue-driver-bundle.svg?branch=master)](https://travis-ci.org/mcfedr/resque-queue-driver-bundle)

## Usage

[PHP Resque](https://github.com/chrisboulton/php-resque) installs two commands into your bin folder.
Generally you should run just one instance of `resque-scheduler` and several of `resque`.

```bash
QUEUE=default APP_INCLUDE=var/bootstrap.php.cache REDIS_BACKEND=127.0.0.1:6379 ./vendor/bin/resque
PREFIX="my_app:" REDIS_BACKEND=127.0.0.1:6379 ./vendor/bin/resque-scheduler
```

* Add `VVERBOSE=1` to the environment to get more logging.

It can be useful to decouple the queueing (master) and execution (slave) of jobs in a
micro service architecture. For this it can be necessary to set specific `kernel_options`
for a worker if they differ from the master. 

* Add `KERNEL_CLASS` with the fully qualified class name of the kernel to override the `kernel.root_dir` option.
  If your kernel is not located in the root namespace (like in symfony flex applications),
  this is the only way to specify the class name of the kernel class.
* Add `SYMFONY_ENV` or `APP_ENV` to override the `kernel.environment` option.
* Add `SYMFONY_DEBUG` or `APP_DEBUG` to override the `kernel.debug` option.

## Install

### Composer

    composer require mcfedr/resque-queue-driver-bundle

### AppKernel

Include the bundle in your AppKernel

    public function registerBundles()
    {
        $bundles = [
            ...
            new Mcfedr\QueueManagerBundle\McfedrQueueManagerBundle(),
            new Mcfedr\ResqueQueueDriverBundle\McfedrResqueQueueDriverBundle(),

## Config

With this bundle installed you can setup your queue manager config similar to this:

    mcfedr_queue_manager:
        managers:
            default:
                driver: resque
                options:
                    host: 127.0.0.1
                    port: 11300
                    default_queue: default
                    track_status: false

This will create a `QueueManager` service named `"mcfedr_queue_manager.default"`

* `host` and `port` - Where is your Redis server
* `default_queue` - Name of the default queue to use
* `track_status` - Set to `true` to enable extra job tracking data to be stored in redis. Useful for debugging

## Options to `QueueManager::put`

* `queue` - A `string` with the name of a queue
* `time` - A `\DateTime` object of when to schedule this job 
* `delay` - Number of seconds from now to schedule this job
* `track_status` - Set to `true` to enable extra job tracking data to be stored in redis. Useful for debugging
