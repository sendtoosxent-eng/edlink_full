<?php

use App\Models\Designation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Designation::query()
            ->whereRaw('LOWER(name) = ?', ['bursar'])
            ->eachById(function (Designation $designation): void {
                $designation->update([
                    'permissions' => collect($designation->permissions ?? [])
                        ->push('accounting.reconciliations.manage')
                        ->unique()
                        ->values()
                        ->all(),
                ]);
            });
    }

    public function down(): void
    {
        // Keep the granted control permission to avoid locking bursars out on rollback.
    }
};
