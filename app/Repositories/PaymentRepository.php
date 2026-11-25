<?php

namespace App\Repositories;

use App\Contracts\PaymentRepositoryInterface;
use App\Models\Payment;

// Satu-satunya tempat query Payment
class PaymentRepository implements PaymentRepositoryInterface
{
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }
}
