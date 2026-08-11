<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use App\Support\DesignationPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class Edl35bd8BursarSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) env('EDL35BD8_BURSAR_EMAIL')));
        $password = (string) env('EDL35BD8_BURSAR_PASSWORD');

        if ($email === '' || $password === '') {
            throw new RuntimeException('Set EDL35BD8_BURSAR_EMAIL and EDL35BD8_BURSAR_PASSWORD before running this seeder.');
        }

        $school = School::where('school_number', 'EDL-35BD8')->firstOrFail();

        $designation = Designation::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Bursar'],
            [
                'description' => 'Finance, payments, expenses, payroll and financial reporting.',
                'permissions' => DesignationPermissions::defaults()['Bursar'],
            ],
        );

        User::updateOrCreate(
            ['school_id' => $school->id, 'email' => $email],
            [
                'designation_id' => $designation->id,
                'staff_number' => 'STF-BURSAR-EDL-35BD8',
                'name' => $school->name.' Bursar',
                'job_title' => 'School Bursar',
                'role' => 'bursar',
                'employment_status' => 'active',
                'joined_at' => now()->toDateString(),
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );

        $this->command?->info("Bursar seeded for {$school->name} ({$school->school_number}).");
        $this->command?->info("Login email: {$email}");
    }
}
