<?php

// File generated from our OpenAPI spec.

namespace Forminator\Stripe\Service\BillingPortal;

class SessionService extends \Forminator\Stripe\Service\AbstractService
{
    /**
     * Creates a session of the customer portal.
     *
     * @param null|array $params
     * @param null|array|\Forminator\Stripe\Util\RequestOptions $opts
     *
     * @throws \Forminator\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Forminator\Stripe\BillingPortal\Session
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/billing_portal/sessions', $params, $opts);
    }
}
