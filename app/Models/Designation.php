<?php

namespace App\Models;

use App\Support\DesignationPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    protected $fillable = ['school_id', 'default_key', 'name', 'description', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accountRole(): ?string
    {
        return DesignationPermissions::definitions()[$this->default_key]['role'] ?? null;
    }

    public function canBeAssignedBy(User $actor): bool
    {
        $managesAccess = array_intersect($this->permissions ?? [], ['staff.designations', 'staff']);

        return ! $managesAccess || $actor->hasPermission('staff.designations');
    }
}
