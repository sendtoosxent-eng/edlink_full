<?php

use App\Models\Designation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Designation::query()->eachById(function (Designation $designation): void {
            $permissions = collect($designation->permissions ?? []);

            $isBursar = strcasecmp($designation->name, 'Bursar') === 0;

            if (! $isBursar && ! $permissions->contains('accounting.dashboard.view') && ! $permissions->contains('accounting')) {
                return;
            }

            $assetPermissions = ['accounting.assets.view'];

            if ($isBursar) {
                $assetPermissions = [
                    'accounting.assets.view',
                    'accounting.assets.manage',
                    'accounting.assets.depreciate',
                ];
            }

            $designation->update([
                'permissions' => $permissions->merge($assetPermissions)->unique()->values()->all(),
            ]);
        });
    }

    public function down(): void
    {
        // Preserve granted access so rolling back cannot unexpectedly lock staff out.
    }
};
