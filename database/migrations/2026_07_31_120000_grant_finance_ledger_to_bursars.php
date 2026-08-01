<?php

use App\Models\Designation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Designation::query()
            ->whereRaw('lower(name) = ?', ['bursar'])
            ->each(function (Designation $designation): void {
                $permissions = collect($designation->permissions ?? [])
                    ->push('finance.ledger')
                    ->unique()
                    ->values()
                    ->all();

                $designation->update(['permissions' => $permissions]);
            });
    }

    public function down(): void
    {
        Designation::query()
            ->whereRaw('lower(name) = ?', ['bursar'])
            ->each(function (Designation $designation): void {
                $permissions = collect($designation->permissions ?? [])
                    ->reject(fn (string $permission) => $permission === 'finance.ledger')
                    ->values()
                    ->all();

                $designation->update(['permissions' => $permissions]);
            });
    }
};