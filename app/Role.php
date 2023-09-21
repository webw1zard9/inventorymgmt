<?php

namespace App;

class Role extends \Spatie\Permission\Models\Role
{
    public function canDelete()
    {
        return ! $this->users->count();
    }
}
