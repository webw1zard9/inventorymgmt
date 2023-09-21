<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $casts = [
        'valid' => 'datetime',
        'expires' => 'datetime',
        'active' => 'boolean',
    ];

    protected $guarded = [];

    public function getValidFromAttribute()
    {
        return $this->valid->format('Y-m-d');
    }

    public function getExpiresOnAttribute()
    {
        return $this->expires->format('Y-m-d');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function license_type()
    {
        return $this->belongsTo(LicenseType::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public static function system_licenses()
    {
        return self::whereNull('user_id')->where('active', 1)->with('license_type');
    }

    public function is_valid()
    {
        return $this->expires->isFuture();
    }

    public function getDisplayNameAttribute()
    {
        return $this->legal_business_name.' - '.$this->license_type->name.' - '.$this->number;
    }

    public function getLegalBusinessNameAttribute($value)
    {
        if (! empty($value)) {
            return $value;
        }

        return ! empty($this->user->details['business_name']) ? $this->user->details['business_name'] : $this->user->name;
    }
}
