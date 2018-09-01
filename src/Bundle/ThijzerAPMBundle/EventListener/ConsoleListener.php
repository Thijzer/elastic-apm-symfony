<?php

/*
 *  This file is property of
 *
 *  (c) Thijs De Paepe <thijs.dp@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thijzer\Bundle\ThijzerAPMBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Stopwatch\Stopwatch;
use Thijzer\Component\Tracker\ApplicationTrackerInterface;

class ConsoleListener
{
    const WATCH_NAME = 'elastic_apm.command';

    protected $kernel;
    protected $tracker;
    protected $stopWatch;
    protected $rules;

    public function __construct(Kernel $kernel, ApplicationTrackerInterface $tracker, $rules)
    {
        $this->kernel = $kernel;
        $this->tracker = $tracker;
        $this->stopwatch = new Stopwatch();
        $this->rules = $rules;
    }

    /**
     * @param ConsoleErrorEvent $event
     */
    public function onConsoleException(ConsoleErrorEvent $event)
    {
        if ($this->rules['exceptions']) {
            $this->tracker->trackException($event->getError());
        }
    }

    public function onConsoleCommand()
    {
        if ($this->rules['commands']) {
            $this->stopwatch->start(self::WATCH_NAME);
        }
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if ($this->rules['commands']) {
            $command = $event->getCommand();
            $input = $event->getInput();

            $properties = [
                'Symfony Command Name' => $command ? $command->getName() : 'unknown',
                'Symfony Environment' => $this->kernel->getEnvironment(),
            ];

            foreach ($input->getOptions() as $key => $value) {
                $key = 'Option: '.$key;
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $properties[$key.'['.$k.']'] = $v;
                    }

                    continue;
                }

                $properties[$key] = $value;
            }

            foreach ($input->getArguments() as $key => $value) {
                $key = 'Argument: '.$key;
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $properties[$key.'['.$k.']'] = $v;
                    }

                    continue;
                }

                $properties[$key] = $value;
            }

            $startTime = $_SERVER['REQUEST_TIME'] ?? false;
            $duration = 0;
            $measurements = [];
            if ($this->stopwatch->isStarted(self::WATCH_NAME)) {
                $profile = $this->stopwatch->stop(self::WATCH_NAME);
                $duration = $profile->getDuration();
                $measurements = [
                    'Memory Usage' => $profile->getMemory(),
                    'Execution Duration' => $profile->getDuration(),
                ];
            }

            $this->tracker->trackEvent(
                'Symfony Command : '.$properties['Symfony Command Name'],
                $startTime,
                $duration,
                $properties,
                $measurements
            );
        }

        // Send any pending telemetry
        $this->tracker->flush();
    }
}
