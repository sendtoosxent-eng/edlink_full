<?php

namespace App\Services;

use App\Models\Designation;
use App\Models\School;
use App\Support\DesignationPermissions;
use Illuminate\Support\Collection;

final class DefaultDesignationService
{
    /**
     * Ensure every school has the standard access designations without
     * overwriting any permissions that an administrator has customised.
     */
    public function ensureFor(School $school): Collection
    {
        foreach (DesignationPermissions::defaults() as $name => $permissions) {
            Designation::firstOrCreate(
                ['school_id' => $school->id, 'name' => $name],
                [
                    'description' => 'Default Edlink access designation.',
                    'permissions' => $permissions,
                ],
            );
        }

        return Designation::where('school_id', $school->id)->orderBy('name')->get();
    }
}
