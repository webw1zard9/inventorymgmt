<?php

namespace App;

class Permission extends \Spatie\Permission\Models\Permission
{
    public function canDelete()
    {
        return ! $this->roles->count() && ! $this->users->count();
    }
}
