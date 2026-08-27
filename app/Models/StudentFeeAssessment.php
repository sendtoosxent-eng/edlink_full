<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFeeAssessment extends Model
{
    protected $fillable = ['school_id', 'student_id', 'term_id', 'fee_structure_id', 'fee_item_code', 'description', 'amount', 'status', 'journal_id', 'idempotency_key', 'created_by', 'posted_at'];

    protected $casts = ['amount' => 'decimal:2', 'posted_at' => 'datetime'];

    public function journal()
    {
        return $this->belongsTo(AccountingJournal::class, 'journal_id');
    }
}
