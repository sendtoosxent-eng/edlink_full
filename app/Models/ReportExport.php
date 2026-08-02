<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;

class ReportExport extends Model
{
    use Prunable;

    protected $fillable = ['school_id','user_id','report','filters','status','disk','path','filename','row_count','failure','completed_at','expires_at'];
    protected $casts = ['filters'=>'array','completed_at'=>'datetime','expires_at'=>'datetime'];

    public function prunable()
    {
        return static::where(fn ($query) => $query->where('expires_at','<',now())->orWhere(fn ($failed) => $failed->where('status','failed')->where('created_at','<',now()->subDays(7))));
    }

    protected function pruning(): void
    {
        if ($this->path) Storage::disk($this->disk)->delete($this->path);
    }
}
