<?php

namespace App\Support;

final class SubscriptionPlans
{
    public const PLANS = [
        'basic' => ['name' => 'Basic', 'limit' => 500, 'description' => 'For schools with up to 500 active learners.'],
        'premium' => ['name' => 'Premium', 'limit' => 1000, 'description' => 'For schools with up to 1,000 active learners.'],
        'enterprise' => ['name' => 'Enterprise', 'limit' => null, 'description' => 'For schools with more than 1,000 active learners.'],
    ];

    public static function limit(string $plan): ?int { return self::PLANS[$plan]['limit'] ?? 500; }
    public static function suggestedFor(int $activeStudents): string { return $activeStudents <= 500 ? 'basic' : ($activeStudents <= 1000 ? 'premium' : 'enterprise'); }
    public static function valid(string $plan): bool { return array_key_exists($plan, self::PLANS); }
}