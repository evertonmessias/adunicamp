<?php

// File generated from our OpenAPI spec.

namespace Forminator\Stripe\Service;

class SetupAttemptService extends \Forminator\Stripe\Service\AbstractService
{
    /**
     * Returns a list of SetupAttempts associated with a provided SetupIntent.
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
        return $this->requestCollection('get', '/v1/setup_attempts', $params, $opts);
    }
}
