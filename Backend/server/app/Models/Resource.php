<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $table = 'resources';
    protected $primaryKey = 'id_resource';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'id_holder',
        'id_user_chief',
        'label',
        'discriminator',
        'isavailable',
        'date_attribution',
        'description'
    ];
    
    /**
     * Get the user that owns the Resource
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function holder()
    {
        return $this->belongsTo(Employee::class, 'id_holder');
    }

    // La relation avec le chef d'unité (id_user_chief)
    public function chief()
    {
        return $this->belongsTo(User::class, 'id_user_chief');
    }
}