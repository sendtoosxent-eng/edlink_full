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
                        ->merge(['accounting.dashboard.view', 'accounting.assets.view'])
                        ->unique()
                        ->values()
                        ->all(),
                ]);
            });
    }

    public function down(): void
    {
        // Preserve access so a rollback cannot unexpectedly lock bursars out.
    }
};
