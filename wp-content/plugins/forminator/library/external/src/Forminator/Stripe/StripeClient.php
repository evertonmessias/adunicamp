<?php

// File generated from our OpenAPI spec.

namespace Forminator\Stripe;

/**
 * Client used to send requests to Stripe's API.
 *
 * @property \Forminator\Stripe\Service\AccountLinkService $accountLinks
 * @property \Forminator\Stripe\Service\AccountService $accounts
 * @property \Forminator\Stripe\Service\ApplePayDomainService $applePayDomains
 * @property \Forminator\Stripe\Service\ApplicationFeeService $applicationFees
 * @property \Forminator\Stripe\Service\BalanceService $balance
 * @property \Forminator\Stripe\Service\BalanceTransactionService $balanceTransactions
 * @property \Forminator\Stripe\Service\BillingPortal\BillingPortalServiceFactory $billingPortal
 * @property \Forminator\Stripe\Service\ChargeService $charges
 * @property \Forminator\Stripe\Service\Checkout\CheckoutServiceFactory $checkout
 * @property \Forminator\Stripe\Service\CountrySpecService $countrySpecs
 * @property \Forminator\Stripe\Service\CouponService $coupons
 * @property \Forminator\Stripe\Service\CreditNoteService $creditNotes
 * @property \Forminator\Stripe\Service\CustomerService $customers
 * @property \Forminator\Stripe\Service\DisputeService $disputes
 * @property \Forminator\Stripe\Service\EphemeralKeyService $ephemeralKeys
 * @property \Forminator\Stripe\Service\EventService $events
 * @property \Forminator\Stripe\Service\ExchangeRateService $exchangeRates
 * @property \Forminator\Stripe\Service\FileLinkService $fileLinks
 * @property \Forminator\Stripe\Service\FileService $files
 * @property \Forminator\Stripe\Service\InvoiceItemService $invoiceItems
 * @property \Forminator\Stripe\Service\InvoiceService $invoices
 * @property \Forminator\Stripe\Service\Issuing\IssuingServiceFactory $issuing
 * @property \Forminator\Stripe\Service\MandateService $mandates
 * @property \Forminator\Stripe\Service\OAuthService $oauth
 * @property \Forminator\Stripe\Service\OrderReturnService $orderReturns
 * @property \Forminator\Stripe\Service\OrderService $orders
 * @property \Forminator\Stripe\Service\PaymentIntentService $paymentIntents
 * @property \Forminator\Stripe\Service\PaymentMethodService $paymentMethods
 * @property \Forminator\Stripe\Service\PayoutService $payouts
 * @property \Forminator\Stripe\Service\PlanService $plans
 * @property \Forminator\Stripe\Service\PriceService $prices
 * @property \Forminator\Stripe\Service\ProductService $products
 * @property \Forminator\Stripe\Service\PromotionCodeService $promotionCodes
 * @property \Forminator\Stripe\Service\Radar\RadarServiceFactory $radar
 * @property \Forminator\Stripe\Service\RefundService $refunds
 * @property \Forminator\Stripe\Service\Reporting\ReportingServiceFactory $reporting
 * @property \Forminator\Stripe\Service\ReviewService $reviews
 * @property \Forminator\Stripe\Service\SetupAttemptService $setupAttempts
 * @property \Forminator\Stripe\Service\SetupIntentService $setupIntents
 * @property \Forminator\Stripe\Service\Sigma\SigmaServiceFactory $sigma
 * @property \Forminator\Stripe\Service\SkuService $skus
 * @property \Forminator\Stripe\Service\SourceService $sources
 * @property \Forminator\Stripe\Service\SubscriptionItemService $subscriptionItems
 * @property \Forminator\Stripe\Service\SubscriptionScheduleService $subscriptionSchedules
 * @property \Forminator\Stripe\Service\SubscriptionService $subscriptions
 * @property \Forminator\Stripe\Service\TaxRateService $taxRates
 * @property \Forminator\Stripe\Service\Terminal\TerminalServiceFactory $terminal
 * @property \Forminator\Stripe\Service\TokenService $tokens
 * @property \Forminator\Stripe\Service\TopupService $topups
 * @property \Forminator\Stripe\Service\TransferService $transfers
 * @property \Forminator\Stripe\Service\WebhookEndpointService $webhookEndpoints
 */
class StripeClient extends BaseStripeClient
{
    /**
     * @var \Forminator\Stripe\Service\CoreServiceFactory
     */
    private $coreServiceFactory;

    public function __get($name)
    {
        if (null === $this->coreServiceFactory) {
            $this->coreServiceFactory = new \Forminator\Stripe\Service\CoreServiceFactory($this);
        }

        return $this->coreServiceFactory->__get($name);
    }
}
