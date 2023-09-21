<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LicenseType extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function licenses()
    {
        return $this->hasMany(License::class);
    }
}
