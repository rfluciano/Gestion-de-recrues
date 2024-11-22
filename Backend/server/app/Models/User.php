<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee; // Import Employee model for validation

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'useraccount';
    protected $primaryKey = 'matricule';
    public $incrementing = false; // Disable auto-incrementing
    public $timestamps = false;
    protected $keyType = 'string';      // Type de la clé primaire

    protected $fillable = [
        'matricule', // Include id_user in fillable to allow mass assignment
        'username',
        'password',
        'isactive',
        'discriminator',
        'id_superior',
        'remember_me'
    ];

    protected $hidden = [
        'password',
    ];

    // Default values for attributes
    protected $attributes = [
        'isactive' => false,
        'discriminator' => 'unitychief'
    ];

    public function subAccounts()
    {
        return $this->hasMany(UserAccount::class, 'id_superior', 'matricule');
    }

    public function Manage_resource()
    {
        return $this->hasMany(Resource::class, 'id_user_chief', 'matricule');
    }

    public function Possess_resource()
    {
        return $this->hasMany(Resource::class, 'id_user_holder', 'matricule');
    }


    public function employee()
    {
        return $this->hasOne(Employee::class, 'matricule', 'matricule');
    }


    /**
     * Boot method for the model.
     */
    protected static function boot()
    {
        parent::boot();
        // Add a creating event to validate id_user
        static::creating(function ($user) {
            // Ensure id_user is provided and exists in the employee matricule column
            $exists = Employee::where('matricule', $user->matricule)->exists();
            if (!$exists) {
                throw new \Exception('The matricule must exist in the employee matricule column.');
            }
        });
    }

    /**
     * Get the identifier that will be stored in the JWT token.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Usually the primary key
    }

    public function findForPassport($identifier)
    {
        return $this->where('username', $identifier)
                    ->orWhere('matricule', $identifier)
                    ->first();
    }

    /**
     * Return a key-value array containing any custom claims to be added to the JWT token.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
