<?php

namespace App\Http\Controllers\Api;

use App\Contracts\InvoiceRepositoryInterface;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\PayInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private InvoiceRepositoryInterface $invoices,
        private PaymentService $payments
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success(InvoiceResource::collection($this->invoices->forUser($request->user())));
    }

    public function show(Invoice $invoice): JsonResponse
    {
        Gate::authorize('view', $invoice);

        return $this->success(new InvoiceResource($invoice->load('subscription.plan', 'subscription.members', 'payments')));
    }

    // Simulasi pembayaran: tidak terhubung ke payment gateway sungguhan
    public function pay(PayInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('pay', $invoice);

        $payment = $this->payments->pay(
            $invoice,
            PaymentMethod::from($request->validated('payment_method')),
            ['simulate' => app()->isProduction() ? 'success' : $request->validated('simulate', 'success')]
        );

        if ($payment->status === PaymentStatus::Failed) {
            return $this->error(
                'Pembayaran gagal (simulasi). Invoice masih menunggu pembayaran, silakan coba lagi.',
                402,
                ['payment' => (new PaymentResource($payment))->resolve()]
            );
        }

        $invoice = $invoice->fresh()->load('subscription.plan', 'subscription.members', 'payments');

        return $this->success(new InvoiceResource($invoice), 'Pembayaran berhasil (simulasi).');
    }
}
