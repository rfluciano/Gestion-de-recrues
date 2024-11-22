<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'requests';

    // Define the primary key
    protected $primaryKey = 'id_request';
    public $incrementing = true;
    public $timestamps = true;

    // Allow mass assignment for the following fields
    protected $fillable = [
        'id_requester',     // Chef d'unité qui crée la requête
        'id_beneficiary',   // Employé qui recevra la ressource
        'id_resource',      // Ressource demandée
        'id_receiver',      // Chef responsable de la ressource
        'request_date',     // Date de la requête
    ];

    /**
     * Get the requester (chef d'unité).
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'id_requester', 'matricule')->withDefault();
    }

    /**
     * Get the beneficiary (employé qui recevra la ressource).
     */
    public function beneficiary()
    {
        return $this->belongsTo(Employee::class, 'id_beneficiary', 'matricule')->withDefault();
    }

    /**
     * Get the resource associated with the request.
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class, 'id_resource', 'id_resource');
    }

    /**
     * Get the receiver (chef responsable de la ressource).
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'id_receiver', 'matricule')->withDefault();
    }

    /**
     * Scope for filtering by request date.
     */
    public function scopeByRequestDate($query, $date)
    {
        return $query->where('request_date', $date);
    }

    /**
     * Get the validation associated with the request.
     */
    public function validation()
    {
        return $this->hasOne(Validation::class, 'id_request', 'id_request')->withDefault();
    }
}
