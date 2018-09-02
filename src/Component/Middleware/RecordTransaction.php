<?php

/*
 *  This file is property of
 *
 *  (c) Thijs De Paepe <thijs.dp@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ElasticAPM\Component\Middleware;

use Closure;
use Illuminate\Routing\Route;
use PhilKra\Agent;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordTransaction
{
    // /**
    //  * @var Agent
    //  */
    // private $apmAgent;

    // public function __construct(Agent $apmAgent)
    // {
    //     $this->apmAgent = $apmAgent;
    // }

    /**
     * @TODO we need keep this as example up until we don't need it any more
     */
    public function handle(Request $request, Closure $next)
    {
        $transaction = $this->apmAgent->startTransaction(Uuid::uuid4()->toString());

        // await the outcome
        $response = $next($request);

        // set the response data
        $transaction->setResponseContext([
            'status_code' => $response->getStatusCode(),
            'headers' => $this->formatHeaders($response->headers->all()),
            'headers_sent' => true,
            'finished' => true,
        ]);

        // set the user
        $transaction->setUserContext([
            'id' => optional($request->user())->id,
            'email' => optional($request->user())->email,
        ]);

        // set the meta details
        $transaction->setMeta([
            'result' => $response->getStatusCode(),
            'type' => 'request',
        ]);

        // add the spans
        $transaction->setSpans(); // fetch mysql logged data

        // stop the transaction
        $transaction->stop(
            $this->getDuration(LARAVEL_START)
        );

        // update the name
        $transaction->setTransactionName(
            $this->getTransactionName($request->getBaseUrl())
        );

        return $response;
    }

    // public function terminate(Request $request, Response $response)
    // {
    //     $this->apmAgent->send();
    // }

    // protected function getTransactionName(Route $route)
    // {
    //     // fix leading /
    //     if ('/' !== $route->uri) {
    //         $route->uri = '/'.$route->uri;
    //     }

    //     return sprintf(
    //         '%s %s',
    //         head($route->methods),
    //         $route->uri
    //     );
    // }

    // protected function getDuration($start): float
    // {
    //     $diff = microtime(true) - $start;
    //     $corrected = $diff * 1000; // convert to miliseconds

    //     return round($corrected, 3);
    // }

    // protected function formatHeaders(array $headers): array
    // {
    //     return collect($headers)->map(function ($values, $header) {
    //         return head($values);
    //     })->toArray();
    // }
}
