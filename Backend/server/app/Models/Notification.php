<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $primaryKey = 'id_notification';

    protected $fillable = [
        'id_user',
        'event_type',
        'message',
        'data',
        'is_read',
    ];

    protected $casts = [
        'data' => 'array', // Cast JSON en tableau PHP
        'is_read' => 'boolean',
    ];

    /**
     * Relation avec le modèle UserAccount.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'matricule');
    }
}

