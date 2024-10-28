<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unity extends Model
{
    use HasFactory;

    protected $table = 'unities'; // Explicitly defining the table name if it's non-standard
    protected $primaryKey = 'id_unity'; // Setting the primary key to 'id_unity'
    public $timestamps = true; // Using timestamps since they are in your migration

    protected $fillable = [
        'id_parent', // Nullable field for parent unity
        'type',      // Type of unity
        'title',     // Title of unity
    ];

    // Define the relationship to itself (for parent-child unity structure)
    public function parent()
    {
        return $this->belongsTo(Unity::class, 'id_parent');
    }

    public function children()
    {
        return $this->hasMany(Unity::class, 'id_parent');
    }
}
