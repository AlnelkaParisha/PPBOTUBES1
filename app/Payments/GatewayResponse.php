<?php

namespace App\Payments;

final class GatewayResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $reference,
        public readonly ?string $message = null
    ) {
    }
}
