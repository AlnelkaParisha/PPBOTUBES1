<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        return $this->success(PlanResource::collection($plans));
    }

    public function show(Plan $plan): JsonResponse
    {
        return $this->success(new PlanResource($plan));
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = Plan::create($request->validated());

        return $this->success(new PlanResource($plan), 'Paket dibuat.', 201);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return $this->success(new PlanResource($plan), 'Paket diperbarui.');
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $inUse = $plan->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Pending->value, SubscriptionStatus::Active->value])
            ->exists();

        if ($inUse) {
            throw new BusinessRuleException('Paket masih dipakai langganan aktif.');
        }

        $plan->delete(); // soft delete

        return $this->success(null, 'Paket dihapus.');
    }
}
