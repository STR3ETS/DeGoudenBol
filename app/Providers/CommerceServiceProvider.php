<?php

namespace App\Providers;

use App\Domain\Commerce\Contracts\AccountingGateway;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Gateways\FakePaymentGateway;
use App\Domain\Commerce\Gateways\MolliePaymentGateway;
use App\Domain\Commerce\Gateways\NullAccountingGateway;
use Illuminate\Support\ServiceProvider;

class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FakePaymentGateway::class);

        $this->app->bind(PaymentGateway::class, function () {
            return match (config('commerce.payment_driver')) {
                'mollie' => $this->app->make(MolliePaymentGateway::class),
                default => $this->app->make(FakePaymentGateway::class),
            };
        });

        $this->app->bind(AccountingGateway::class, NullAccountingGateway::class);
    }
}
