<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Peran extends SpatieRole
{
    public $timestamps = false;

    protected $fillable = ['name', 'guard_name'];
}
