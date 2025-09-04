<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $primaryKey = 'id';
    protected $fillable = [
        'uuid',
        'title',
        'description',
        'file_server',
        'file_url',
        'duration',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'repeat_days',
        'created_by',
        'modified_by',
        'file_local',
        'last_synced_at',
    ];
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'monitor_contents', 'content_id', 'id_departments')
            ->withPivot(['is_visible_to_parent', 'is_tayang_request'])
            ->withTimestamps();
    }
    
}