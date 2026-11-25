<?php

namespace App\Services;

use App\Billing\BillingService;
use App\Contracts\SubscriptionRepositoryInterface;
use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    private const MAX_FAMILY_MEMBERS = 6;

    public function __construct(
        private SubscriptionRepositoryInterface $subscriptions,
        private BillingService $billing,
        private InvoiceService $invoices
    ) {
    }

    /** @param array<int, array{name: string, email: string}>|null $members wajib untuk paket family */
    public function subscribe(User $user, Plan $plan, ?array $members = null): Subscription
    {
        if (! $plan->is_active) {
            throw new BusinessRuleException('Paket tidak tersedia.');
        }

        if ($this->subscriptions->hasActiveOrPending($user)) {
            throw new BusinessRuleException('Anda masih memiliki langganan aktif atau yang menunggu pembayaran.');
        }

        $members = $this->normalizeMembers($plan, $members);

        return DB::transaction(function () use ($user, $plan, $members) {
            $start = today();

            $subscription = $this->subscriptions->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Pending,
                'period_start' => $start,
                'period_end' => $this->billing->calculatePeriodEnd($plan, $start),
                'members_count' => max(1, count($members)),
            ]);

            if ($members !== []) {
                $this->subscriptions->syncMembers($subscription, $members);
            }

            $this->invoices->createInitial($subscription);

            return $subscription->load('plan', 'members', 'invoices');
        });
    }

    /** @param array<int, array{name: string, email: string}>|null $members */
    public function changePlan(Subscription $subscription, Plan $newPlan, ?array $members = null): Subscription
    {
        $this->assertCanChange($subscription, $newPlan);

        $wasFamily = $subscription->plan->billing_cycle === BillingCycle::Family;
        $toFamily = $newPlan->billing_cycle === BillingCycle::Family;

        if ($toFamily && ! $wasFamily && $members === null) {
            throw new BusinessRuleException('Daftar anggota wajib diisi untuk pindah ke paket family.');
        }

        // null = anggota lama dipertahankan (family ke family tanpa mengubah daftar)
        $members = ($toFamily && $members !== null) ? $this->normalizeMembers($newPlan, $members) : null;
        $membersCount = match (true) {
            ! $toFamily => 1,
            $members !== null => count($members),
            default => $subscription->members_count,
        };

        $result = $this->billing->calculateProration(
            $subscription->plan,
            $newPlan,
            $subscription->period_start,
            $subscription->period_end,
            today()
        );

        return DB::transaction(function () use ($subscription, $newPlan, $toFamily, $members, $membersCount, $result) {
            $this->subscriptions->update($subscription, [
                'previous_plan_id' => $subscription->plan_id,
                'plan_id' => $newPlan->id,
                'period_start' => $result->newPeriodStart,
                'period_end' => $result->newPeriodEnd,
                'members_count' => $membersCount,
                'credit_balance' => $subscription->credit_balance + $result->creditBalance(),
            ]);

            if (! $toFamily) {
                $this->subscriptions->syncMembers($subscription, []); // keluar dari family: anggota dihapus
            } elseif ($members !== null) {
                $this->subscriptions->syncMembers($subscription, $members);
            }

            if ($result->payNow() > 0) {
                $this->invoices->createProration($subscription, $result);
            }

            return $subscription->load('plan', 'previousPlan', 'members', 'invoices');
        });
    }

    // Simulasi: hitung prorata tanpa menyimpan apa pun
    public function previewChange(Subscription $subscription, Plan $newPlan): array
    {
        $this->assertCanChange($subscription, $newPlan);

        $result = $this->billing->calculateProration(
            $subscription->plan,
            $newPlan,
            $subscription->period_start,
            $subscription->period_end,
            today()
        );

        return [
            'old_credit' => $result->oldCredit,
            'new_charge' => $result->newCharge,
            'pay_now' => $result->payNow(),
            'credit_balance_added' => $result->creditBalance(),
            'new_period_start' => $result->newPeriodStart->toDateString(),
            'new_period_end' => $result->newPeriodEnd->toDateString(),
        ];
    }

    public function cancel(Subscription $subscription): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            throw new BusinessRuleException('Hanya langganan aktif yang bisa dibatalkan.');
        }

        $this->subscriptions->update($subscription, [
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $subscription->load('plan');
    }

    private function assertCanChange(Subscription $subscription, Plan $newPlan): void
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            throw new BusinessRuleException('Hanya langganan aktif yang bisa diganti paketnya.');
        }

        if ($subscription->period_end->lt(today())) {
            throw new BusinessRuleException('Periode langganan sudah berakhir.');
        }

        if (! $newPlan->is_active) {
            throw new BusinessRuleException('Paket tujuan tidak tersedia.');
        }

        if ($newPlan->id === $subscription->plan_id) {
            throw new BusinessRuleException('Paket tujuan sama dengan paket saat ini.');
        }
    }

    /**
     * Anggota hanya berlaku untuk paket family (1 sampai 6 orang, pemilik ikut dihitung).
     * Paket lain: daftar anggota diabaikan.
     *
     * @return array<int, array{name: string, email: string}>
     */
    private function normalizeMembers(Plan $plan, ?array $members): array
    {
        if ($plan->billing_cycle !== BillingCycle::Family) {
            return [];
        }

        if ($members === null || count($members) < 1 || count($members) > self::MAX_FAMILY_MEMBERS) {
            throw new BusinessRuleException('Paket family wajib mengisi anggota, 1 sampai '.self::MAX_FAMILY_MEMBERS.' orang.');
        }

        return array_map(
            fn (array $member) => ['name' => $member['name'], 'email' => $member['email']],
            array_values($members)
        );
    }
}
