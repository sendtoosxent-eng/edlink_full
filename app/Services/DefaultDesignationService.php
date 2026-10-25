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
        foreach (DesignationPermissions::definitions() as $key => $definition) {
            if (Designation::where('school_id', $school->id)->where('default_key', $key)->exists()) {
                continue;
            }
            $aliases = $key === 'dean_of_studies' ? ['Dean of Studies', 'DOS', 'Director of Studies'] : [$definition['name']];
            $existing = Designation::where('school_id', $school->id)->whereNull('default_key')->whereIn('name', $aliases)
                ->orderByRaw('case when name = ? then 0 else 1 end', [$definition['name']])->first();
            if ($existing) {
                $existing->update(['default_key' => $key, 'name' => $key === 'dean_of_studies' ? $definition['name'] : $existing->name]);

                continue;
            }
            Designation::firstOrCreate(
                ['school_id' => $school->id, 'default_key' => $key],
                ['name' => $definition['name'], 'description' => $definition['description'], 'permissions' => $definition['permissions']],
            );
        }

        return Designation::where('school_id', $school->id)->orderBy('name')->get();
    }
}
