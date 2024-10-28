<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'positions';

    // Primary key
    protected $primaryKey = 'id_position';

    // Mass assignable attributes
    protected $fillable = [
        'id_unity',
        'title',
    ];

    // Relationship to the Unity model (assuming a position belongs to a unity)
    public function unity()
    {
        return $this->belongsTo(Unity::class, 'id_unity', 'id_unity');
    }

    
}
