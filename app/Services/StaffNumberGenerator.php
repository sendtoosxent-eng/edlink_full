<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;

class StaffNumberGenerator
{
    public function generate(School $school, User $staff): string
    {
        return 'STF-'.$school->id.'-'.str_pad((string) $staff->id, 6, '0', STR_PAD_LEFT);
    }
}
