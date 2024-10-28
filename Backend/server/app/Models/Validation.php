<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validation extends Model
{
    use HasFactory;

    // Table name if different from 'validations'

    protected $table = 'validations';

    protected $primaryKey = 'id_validation';
    public $incrementing = true;
    public $timestamps = false;

    // The attributes that are mass assignable
    protected $fillable = [
        'id_validator',
        'id_request',
        'validation_date',
        'status',
        'rejection_reason',
    ];

    // Define the relationship with the User model (validator)
    public function validator()
    {
        return $this->belongsTo(User::class, 'id_validator');
    }

    // Define the relationship with the Request model
    public function request()
    {
        return $this->belongsTo(Request::class, 'id_request');
    }
}
