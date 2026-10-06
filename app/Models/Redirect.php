<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    protected $fillable = ['from', 'to', 'status', 'hits', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        // ApplyRedirects caches the active from=>id map for 5 minutes; without
        // this, a redirect created or edited in admin wouldn't take effect
        // until that cache expired. Mirrors Setting's own cache-busting.
        static::saved(fn () => Cache::forget('redirects.map'));
        static::deleted(fn () => Cache::forget('redirects.map'));
    }
}
