<?php

// File generated from our OpenAPI spec.

namespace Forminator\Stripe\Service\Sigma;

class ScheduledQueryRunService extends \Forminator\Stripe\Service\AbstractService
{
    /**
     * Returns a list of scheduled query runs.
     *
     * @param null|array $params
     * @param null|array|\Forminator\Stripe\Util\RequestOptions $opts
     *
     * @throws \Forminator\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Forminator\Stripe\Collection
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/sigma/scheduled_query_runs', $params, $opts);
    }

    /**
     * Retrieves the details of an scheduled query run.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Forminator\Stripe\Util\RequestOptions $opts
     *
     * @throws \Forminator\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Forminator\Stripe\Sigma\ScheduledQueryRun
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/sigma/scheduled_query_runs/%s', $id), $params, $opts);
    }
}
