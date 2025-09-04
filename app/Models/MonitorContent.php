<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MonitorContent extends Model
{
     protected $table = 'monitor_contents';
     protected $fillable = [
        'id_departments', 
        'content_id',  
        'is_visible_to_parent', 
        'is_tayang_request'
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id', 'content_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'id_departments', 'id_departments');
    }
}




