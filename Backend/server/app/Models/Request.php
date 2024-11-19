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
        'id_requester',
        'id_resource',
        'id_receiver',
        'request_date',
    ];

    /**
     * Get the requester (user) who made the request.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'id_requester', 'id_user')->withDefault();
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'id_receiver', 'id_user')->withDefault();
    }

    /**
     * Get the resource associated with the request.
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class, 'id_resource', 'id_resource');
    }

    /**
     * Scope for filtering by request date.
     */
    public function scopeByRequestDate($query, $date)
    {
        return $query->where('request_date', $date);
    }

    public function validation()
    {
        return $this->hasOne(Validation::class, 'id_request', 'id_request')->withDefault();
    }

}
