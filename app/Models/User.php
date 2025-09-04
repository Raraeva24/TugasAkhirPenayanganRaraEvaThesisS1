<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
      public function departments()
    {
        return $this->belongsTo(Department::class, 'id_departments');
    }
}
