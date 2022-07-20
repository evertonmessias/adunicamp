<?php

// File generated from our OpenAPI spec.

namespace Forminator\Stripe\Service\Reporting;

class ReportTypeService extends \Forminator\Stripe\Service\AbstractService
{
    /**
     * Returns a full list of Report Types. (Requires a <a
     * href="https://stripe.com/docs/keys#test-live-modes">live-mode API key</a>.).
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
        return $this->requestCollection('get', '/v1/reporting/report_types', $params, $opts);
    }

    /**
     * Retrieves the details of a Report Type. (Requires a <a
     * href="https://stripe.com/docs/keys#test-live-modes">live-mode API key</a>.).
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Forminator\Stripe\Util\RequestOptions $opts
     *
     * @throws \Forminator\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Forminator\Stripe\Reporting\ReportType
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/reporting/report_types/%s', $id), $params, $opts);
    }
}
