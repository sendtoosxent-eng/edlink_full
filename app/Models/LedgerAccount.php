<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LedgerAccount extends Model
{
    protected $fillable = ['school_id', 'parent_id', 'code', 'name', 'account_class', 'subtype', 'normal_balance', 'currency', 'accepts_postings', 'is_control_account', 'is_system', 'is_active', 'archived_at', 'created_by'];

    protected $casts = ['accepts_postings' => 'boolean', 'is_control_account' => 'boolean', 'is_system' => 'boolean', 'is_active' => 'boolean', 'archived_at' => 'datetime'];

    protected static function booted(): void
    {
        static::deleting(function (self $account) {
            if ($account->is_system || $account->lines()->exists()) {
                throw ValidationException::withMessages(['account' => 'System accounts and accounts with journal history cannot be deleted. Archive the account instead.']);
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function lines()
    {
        return $this->hasMany(AccountingJournalLine::class);
    }

    public function hasPostedActivity(): bool
    {
        return $this->lines()->whereHas('journal', fn ($q) => $q->where('status', 'posted'))->exists();
    }
}
