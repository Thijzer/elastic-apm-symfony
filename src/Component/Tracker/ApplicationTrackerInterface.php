<?php

/*
 *  This file is property of
 *
 *  (c) Thijs De Paepe <thijs.dp@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ElasticAPM\Component\Tracker;

interface ApplicationTrackerInterface
{
    /**
     * Tracks a run time exception.
     *
     * @param \Throwable        $exception
     * @param array             $properties   an array of name to value pairs
     * @param array             $measurements an array of name to double pairs
     *
     * @return self
     */
    public function trackException(\Throwable $exception, array $properties = [], array $measurements = []);

    /**
     * Tracks a request.
     *
     * @param string $name         a friendly name of the request
     * @param string $url          the url of the request
     * @param int    $startTime    the timestamp at which the request started
     * @param int    $duration     the duration, in milliseconds, of the request
     * @param array  $properties   an array of name to value pairs
     * @param array  $measurements an array of name to double pairs
     *
     * @return self
     */
    public function trackRequest(
        string $name,
        string $url,
        int $startTime,
        int $duration,
        array $properties = [],
        array $measurements = []
    );

    /**
     * Tracks a console command.
     *
     * @param string $name         the command name
     * @param int    $startTime    the timestamp at which the command started
     * @param int    $duration     the duration, in milliseconds
     * @param array  $properties   an array of name to value pairs
     * @param array  $measurements an array of name to double pairs
     *
     * @return self
     */
    public function trackConsoleCommand(
        string $name,
        int $startTime,
        int $duration,
        array $properties = [],
        array $measurements = []
    );

    /**
     * Tracks an event.
     *
     * @param string $name
     * @param array  $properties   an array of name to value pairs
     * @param array  $measurements an array of name to double pairs
     *
     * @return self
     */
    public function trackEvent(
        string $name,
        array $properties = [],
        array $measurements = []
    );

    /**
     * Tracks a metric.
     *
     * @param string $name       name of the metric
     * @param float  $value      value of the metric
     * @param array  $properties an array of name to value pairs
     *
     * @return self
     */
    public function trackMetric(string $name, float $value, array $properties = []);

    /**
     * Tracks a message.
     *
     * @param string $message    the trace message
     * @param array  $properties an array of name to value pairs
     *
     * @return self
     */
    public function trackMessage(string $message, array $properties = []);

    /**
     * Tracks a dependency.
     *
     * @param string $name         name of the dependency
     * @param int    $type         the Dependency type of value being sent
     * @param string $commandName  command/Method of the dependency
     * @param int    $startTime    the timestamp at which the request started
     * @param int    $durationInMs the duration, in milliseconds, of the request
     * @param bool   $isSuccessful whether or not the request was successful
     * @param int    $resultCode   the result code of the request
     * @param bool   $isAsync      whether or not the request was asyncronous
     * @param array  $properties   an array of name to value pairs
     *
     * @return self
     */
    public function trackDependency(
        string $name,
        int $type = 0,
        string $commandName = null,
        int $startTime = null,
        int $durationInMs = 0,
        bool $isSuccessful = true,
        int $resultCode = null,
        bool $isAsync = null,
        array $properties = []
    );

    /**
     * Flushes all tracked Messages
     *
     * @return void
     */
    public function flush(): void;
}
