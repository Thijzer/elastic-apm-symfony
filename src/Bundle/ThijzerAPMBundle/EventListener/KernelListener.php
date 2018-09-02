<?php

/*
 *  This file is property of
 *
 *  (c) Thijs De Paepe <thijs.dp@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ElasticAPM\Bundle\ElasticAPMAPMBundle\EventListener;

use ElasticAPM\Component\Tracker\ApplicationTrackerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Attaching actions to kernel events.
 */
class KernelListener
{
    const WATCH_NAME = 'elastic_apm.request';

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
     * Handles the onKernelException event.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->rules['exceptions']) {
            $exception = $event->getException();

            if (!$exception instanceof HttpExceptionInterface) {
                $this->tracker->trackException($exception);
            }
        }
    }

    /**
     * Handles the onKernelResponse event.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->rules['requests'] && $this->isTrackableRequest($event)) {
            $this->stopwatch->start(self::WATCH_NAME);
        }
    }

    /**
     * Handles the onKernelTerminate event.
     *
     * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        if ($this->rules['requests'] && $this->isTrackableRequest($event)) {
            $request = $event->getRequest();
            $response = $event->getResponse();

            $route = $request->get('_route') ?: 'unknown';
            $url = $request->getSchemeAndHttpHost().$event->getRequest()->getRequestUri();
            $startTime = $request->server->get('REQUEST_TIME');
            $duration = 0;
            $measurements = [];
            if ($this->stopwatch->isStarted(self::WATCH_NAME)) {
                $profile = $this->stopwatch->stop(self::WATCH_NAME);
                $duration = $profile->getDuration();
                $measurements = [
                    'Memory Usage' => $profile->getMemory(),
                ];
            }
            $properties = [
                'httpResponseCode' => $response->getStatusCode(),
                'isSuccessful' => $response->isSuccessful(),
                'Symfony Controller' => $this->getControllerName($request),
                'Symfony Route' => $route,
                'Symfony Environment' => $this->kernel->getEnvironment(),
            ];

            $this->tracker->trackRequest($route, $url, $startTime, $duration, $properties, $measurements);
        }

        // Send any pending telemetry
        $this->tracker->flush();
    }

    private function isTrackableRequest(KernelEvent $event)
    {
        $uri = $event->getRequest()->getRequestUri();
        if ($event->isMasterRequest() && !preg_match('#^/_profiler|^/_wdt#', $uri)) {
            return true;
        }

        return false;
    }

    private function getControllerName(Request $request)
    {
        if (!$controller = $request->attributes->get('_controller')) {
            return false;
        }

        if (is_array($controller)) {
            return $controller;
        }

        if (is_object($controller)) {
            if (method_exists($controller, '__invoke')) {
                return $controller;
            }

            return false;
        }

        return $controller;
    }
}
