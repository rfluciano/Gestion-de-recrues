<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $table = 'positions'; // Ensure correct table name

    protected $primaryKey = 'id_position';

    protected $fillable = [
        'id_unity',
        'title',
        'isavailable'
    ];

    public function unity()
    {
        return $this->belongsTo(Unity::class, 'id_unity', 'id_unity');
    }

    // Define the one-to-one relationship with Employee
    public function employee()
    {
        return $this->hasOne(Employee::class, 'id_position', 'id_position');
    }
}
