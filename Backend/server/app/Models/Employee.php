<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees'; // Ensure the table name matches your database
    protected $primaryKey = 'matricule';
    public $incrementing = false; // Matricule is not auto-incrementing in the database
    public $timestamps = false;
    protected $keyType = 'string';      // Type de la clé primaire
    // Assuming you are not using created_at/updated_at timestamps

    protected $fillable = [
        'id_position',
        'name',
        'firstname',
        'isequipped',
        'date_entry',
        'id_superior'
    ];

    public function getWeeklyEmployeeCount()
    {
        $startOfWeek = Carbon::now()->startOfWeek(); // Monday
        $endOfWeek = Carbon::now()->endOfWeek(); // Sunday

        return Employee::select(DB::raw('DATE(date_entry) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('date_entry', [$startOfWeek, $endOfWeek])
            ->groupBy(DB::raw('DATE(date_entry)'))
            ->get();
    }

    public function getMonthlyEmployeeCount()
    {
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        return Employee::select(DB::raw('MONTH(date_entry) as month'), DB::raw('COUNT(*) as count'))
            ->whereBetween('date_entry', [$startOfYear, $endOfYear])
            ->groupBy(DB::raw('MONTH(date_entry)'))
            ->get();
    }

    // Define the relationship with the Position model
    public function position()
    {
        return $this->belongsTo(Position::class, 'id_position');
    }

    public function resource()
    {
        return $this->hasMany(Resource::class, 'id_holder', 'matricule');
    }
    // Override the save method to customize Matricule generation
    public function save(array $options = [])
    {
        // Generate matricule if absent, using the entry date
        if (empty($this->matricule)) {
            $this->matricule = $this->generateMatricule($this->date_entry);
        }
        return parent::save($options);
    }    

    // Function to generate the personalized Matricule
    public function generateMatricule($entryDate = null)
    {
        $year = Carbon::now()->format('Y'); // Get the current year

        // Use DB transaction with a lock to avoid race conditions
        return \DB::transaction(function () use ($year) {
            // Lock the employees table while generating matricule to avoid race conditions
            $lastMatricule = Employee::whereYear('date_entry', $year)
                ->orderBy('matricule', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastMatricule) {
                // Extract the last 3 digits from the matricule (e.g., '2024-001' -> '001')
                $lastNumber = (int) substr($lastMatricule->matricule, -3);
                // Increment the number
                $newNumber = $lastNumber + 1;
            } else {
                // Start at 1 if no matricule for the current year exists
                $newNumber = 1;
            }

            // Format the matricule as 'YYYY-XXX' (e.g., '2024-002')
            return sprintf('%s-%03d', $year, $newNumber);
        });
    }

    public function subAccounts()
    {
        return $this->hasMany(Employee::class, 'id_superior', 'matricule');
    }

    

}
