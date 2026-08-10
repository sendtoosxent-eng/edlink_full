<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('emergency_contact_name')->nullable()->after('phone');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->string('national_id', 100)->nullable()->after('emergency_contact_phone');
            $table->string('contract_type')->default('permanent')->after('employment_status');
            $table->date('probation_ends_at')->nullable()->after('contract_type');
            $table->string('bank_name')->nullable()->after('probation_ends_at');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number', 100)->nullable()->after('bank_account_name');
            $table->string('staff_document_path')->nullable()->after('bank_account_number');
            $table->string('staff_document_type')->nullable()->after('staff_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'emergency_contact_name', 'emergency_contact_phone', 'national_id', 'contract_type',
                'probation_ends_at', 'bank_name', 'bank_account_name', 'bank_account_number',
                'staff_document_path', 'staff_document_type',
            ]);
        });
    }
};
