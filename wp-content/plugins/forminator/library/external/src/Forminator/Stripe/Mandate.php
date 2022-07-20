<?php

// File generated from our OpenAPI spec.

namespace Forminator\Stripe;

/**
 * A Mandate is a record of the permission a customer has given you to debit their
 * payment method.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property \Forminator\Stripe\StripeObject $customer_acceptance
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Forminator\Stripe\StripeObject $multi_use
 * @property string|\Forminator\Stripe\PaymentMethod $payment_method ID of the payment method associated with this mandate.
 * @property \Forminator\Stripe\StripeObject $payment_method_details
 * @property \Forminator\Stripe\StripeObject $single_use
 * @property string $status The status of the mandate, which indicates whether it can be used to initiate a payment.
 * @property string $type The type of the mandate.
 */
class Mandate extends ApiResource
{
    const OBJECT_NAME = 'mandate';

    use ApiOperations\Retrieve;
}
