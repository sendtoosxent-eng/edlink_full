<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class NotificationVisibility
{
    public static function forUser(User $user, string $alias = 'school_notifications'): Builder
    {
        $query = DB::table($alias === 'school_notifications' ? $alias : 'school_notifications as '.$alias)
            ->where($alias.'.school_id', $user->school_id)
            ->where(fn ($items) => $items->whereNull($alias.'.user_id')->orWhere($alias.'.user_id', $user->id));

        if (in_array($user->role, ['parent', 'student'], true)) {
            $query->whereNotIn($alias.'.category', ['system_health', 'system', 'licence', 'license', 'payroll'])
                // Older health and licence alerts were uncategorized broadcasts.
                ->where($alias.'.title', 'not like', '%system health%')
                ->where($alias.'.title', '!=', 'Licence renewal reminder');
        }

        return $query;
    }
}
