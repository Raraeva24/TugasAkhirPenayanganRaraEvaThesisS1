<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $primaryKey = 'id_departments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id_departments', 'name_departments', 'parent_id', 'uuid'];

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function contents()
    {
        return $this->belongsToMany(Content::class, 'monitor_contents', 'id_departments', 'content_id')
            ->withPivot(['is_visible_to_parent', 'is_tayang_request'])
            ->withTimestamps();
    }
}
