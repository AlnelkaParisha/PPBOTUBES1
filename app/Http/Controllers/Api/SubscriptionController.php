<?php

namespace App\Http\Controllers\Api;

use App\Contracts\SubscriptionRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\ChangePlanRequest;
use App\Http\Requests\Subscription\SubscribeRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SubscriptionService $service,
        private SubscriptionRepositoryInterface $subscriptions
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->subscriptions->forUser($request->user());

        return $this->success(SubscriptionResource::collection($items));
    }

    public function store(SubscribeRequest $request): JsonResponse
    {
        $plan = Plan::findOrFail($request->validated('plan_id'));

        $subscription = $this->service->subscribe(
            $request->user(),
            $plan,
            $request->validated('members')
        );

        return $this->success(
            new SubscriptionResource($subscription),
            'Langganan dibuat. Silakan bayar invoice untuk mengaktifkan.',
            201
        );
    }

    public function show(Subscription $subscription): JsonResponse
    {
        Gate::authorize('view', $subscription);

        return $this->success(new SubscriptionResource($subscription->load('plan', 'previousPlan', 'members', 'invoices')));
    }

    public function changePlan(ChangePlanRequest $request, Subscription $subscription): JsonResponse
    {
        Gate::authorize('update', $subscription);

        $newPlan = Plan::findOrFail($request->validated('plan_id'));
        $updated = $this->service->changePlan($subscription, $newPlan, $request->validated('members'));

        return $this->success(new SubscriptionResource($updated), 'Paket berhasil diganti.');
    }

    public function previewChange(ChangePlanRequest $request, Subscription $subscription): JsonResponse
    {
        Gate::authorize('update', $subscription);

        $newPlan = Plan::findOrFail($request->validated('plan_id'));

        return $this->success($this->service->previewChange($subscription, $newPlan), 'Simulasi prorata.');
    }

    public function cancel(Subscription $subscription): JsonResponse
    {
        Gate::authorize('update', $subscription);

        $updated = $this->service->cancel($subscription);

        return $this->success(new SubscriptionResource($updated), 'Langganan dibatalkan. Masih bisa dipakai sampai periode berakhir.');
    }
}
