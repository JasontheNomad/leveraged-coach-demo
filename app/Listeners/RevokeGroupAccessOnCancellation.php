<?php

namespace App\Listeners;

use App\Models\GroupStripePrice;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class RevokeGroupAccessOnCancellation
{
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;

        if (($payload['type'] ?? null) !== 'customer.subscription.deleted') {
            return;
        }

        $object = $payload['data']['object'] ?? [];
        $customerId = $object['customer'] ?? null;

        if (! $customerId) {
            Log::warning('Subscription cancellation: no customer id in payload.');

            return;
        }

        $user = User::where('stripe_id', $customerId)->first();

        if (! $user) {
            Log::info('Subscription cancellation: no local user for Stripe customer.', [
                'stripe_customer' => $customerId,
            ]);

            return;
        }

        $priceIds = collect($object['items']['data'] ?? [])
            ->pluck('price.id')
            ->filter()
            ->unique();

        foreach ($priceIds as $priceId) {
            $mapping = GroupStripePrice::where('stripe_price', $priceId)->first();

            if (! $mapping) {
                Log::info('Subscription cancellation: price not mapped to any group.', [
                    'user_id'      => $user->id,
                    'stripe_price' => $priceId,
                ]);

                continue;
            }

            $group = $mapping->group;

            if (! $group) {
                continue;
            }

            if ($group->revoke_on_cancel === true) {
                $user->groups()->detach($group->id);

                Log::info('Subscription cancellation: revoked group access.', [
                    'user_id'      => $user->id,
                    'group_slug'   => $group->slug,
                    'stripe_price' => $priceId,
                ]);
            } else {
                Log::info('Subscription cancellation: group not set to revoke on cancel, skipped.', [
                    'user_id'      => $user->id,
                    'group_slug'   => $group->slug,
                    'stripe_price' => $priceId,
                ]);
            }
        }
    }
}
