<?php

/*
 *  This file is property of
 *
 *  (c) Thijs De Paepe <thijs.dp@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thijzer\Component\Tracker;

use PhilKra\Agent;
use Ramsey\Uuid\Uuid;

class ApmAgent implements ApplicationTrackerInterface
{
    /**
     * @var Agent
     */
    private $agent;

    /**
     * @var \PhilKra\Events\Transaction
     */
    private $transaction;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
        $this->transaction = $this->agent->startTransaction(Uuid::uuid4()->toString());
    }

    /**
     * @inheritdoc
     */
    public function trackRequest(
        string $name,
        string $url,
        int $startTime,
        int $duration,
        array $properties = [],
        array $measurements = []
    ) {
        $this->transaction->setTransactionName($url);

        $this->transaction->setSpans([
            'name' => 'Symfony Request',
            'type' => 'symfony.app.request',
            'start' => $startTime,
            'duration' => $duration,
            'properties' => $properties,
            'measurements' => $measurements,
        ]);

        $this->transaction->stop($duration);

    }

    /**
     * @inheritdoc
     */
    public function trackEvent(string $name, array $properties = [], array $measurements = [])
    {
        // TODO: Implement trackEvent() method.
    }

    /**
     * @inheritdoc
     */
    public function trackMetric(string $name, float $value, array $properties = [])
    {
        // TODO: Implement trackMetric() method.
    }

    /**
     * @inheritdoc
     */
    public function trackMessage(string $message, array $properties = [])
    {
        // TODO: Implement trackMessage() method.
    }

    /**
     * @inheritdoc
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
    )
    {
        // TODO: Implement trackDependency() method.
    }

    /**
     * @inheritdoc
     */
    public function trackException(\Throwable $exception, array $properties = [], array $measurements = [])
    {
        $this->transaction->setSpans([
            'name' => 'App Exception',
            'type' => 'symfony.app.exception',
            'properties' => $properties,
            'measurements' => $measurements,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function flush(): void
    {
        $this->agent->send();
    }
}
