<?php

namespace App\Providers;

use App\Contracts\InvoiceRepositoryInterface;
use App\Contracts\PaymentRepositoryInterface;
use App\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SubscriptionRepository;
use Illuminate\Support\ServiceProvider;

// Dependency Injection: interface dihubungkan ke implementasinya di sini.
class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
        InvoiceRepositoryInterface::class => InvoiceRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
    ];
}
