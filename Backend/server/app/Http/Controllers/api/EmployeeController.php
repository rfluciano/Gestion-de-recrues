<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log; // Import the Log facade
use Exception;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    // Function to create a new employee
    public function create(Request $request)
    {
        try {
            // Validate incoming request data
            $request->validate([
                'id_user' => 'nullable|integer|exists:useraccount,id_user|unique:employees,id_user', // Ensure id_user is unique
                'id_position' => 'integer|exists:positions,id_position',
                'name' => 'required|string|max:255',
                'firstname' => 'required|string|max:255',
                'status' => 'required|string|max:255',
                'date_entry' => 'required|date',
            ]);

            // Create a new employee instance
            $employee = new Employee();
            $employee->id_user = $request->id_user;
            $employee->id_position = $request->id_position;
            $employee->name = $request->name;
            $employee->firstname = $request->firstname;
            $employee->status = $request->status;
            $employee->date_entry = $request->date_entry;

            // Extract the year from date_entry
            $yearFromDateEntry = Carbon::parse($request->date_entry)->format('Y');

            // Generate a unique matricule based on the year from date_entry
            $employee->matricule = $this->generateUniqueMatricule($yearFromDateEntry);

            // Save the employee
            $employee->save();

            // Return a response
            return response()->json(['message' => 'Employee created successfully!', 'Matricule' => $employee->matricule], 201);
        } catch (Exception $e) {
            // Log the error message
            Log::error('Employee creation failed: ' . $e->getMessage(), [
                'request' => $request->all(), // Log the request data for debugging
                'error' => $e,
            ]);

            // Return an error response
            return response()->json(['message' => 'Failed to create employee.', 'error' => $e->getMessage()], 500);
        }
    }

    // Function to generate a unique matricule based on the given year
    private function generateUniqueMatricule($year)
    {
        $maxMatricule = Employee::where('matricule', 'like', "{$year}-%")
                                ->orderBy('matricule', 'desc')
                                ->first();

        if ($maxMatricule) {
            // Extract the last number from the matricule
            $lastNumber = (int) substr($maxMatricule->matricule, -3);
            // Increment the number
            $newNumber = $lastNumber + 1;
        } else {
            // Start at 1 if no matricule for the specified year exists
            $newNumber = 1;
        }

        // Format the matricule as 'YYYY-XXX'
        return sprintf('%s-%03d', $year, $newNumber);
    }



    public function show(Request $request)
    {
        try {
            // Optionally, you can apply filters based on query parameters
            $employees = Employee::when($request->id_position, function($query) use ($request) {
                    return $query->where('id_position', $request->id_position);
                })
                ->when($request->status, function($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->get();

            return response()->json(['employees' => $employees], 200);
        } catch (Exception $e) {
            Log::error('Failed to fetch employees: ' . $e->getMessage(), [
                'error' => $e,
            ]);

            return response()->json(['message' => 'Failed to fetch employees.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validate incoming request data
            $request->validate([
                'id_user' => 'integer|exists:useraccount,id_user', // Adjust if your user id column is named differently
                'id_position' => 'integer|exists:positions,id_position',
                'name' => 'string|max:255',
                'firstname' => 'string|max:255',
                'status' => 'string|max:255',
                'date_entry' => 'date',
            ]);

            // Find the employee by ID
            $employee = Employee::findOrFail($id);

            // Update employee attributes
            $employee->id_user = $request->id_user ?? $employee->id_user;
            $employee->id_position = $request->id_position ?? $employee->id_position;
            $employee->name = $request->name ?? $employee->name;
            $employee->firstname = $request->firstname ?? $employee->firstname;
            $employee->status = $request->status ?? $employee->status;
            $employee->date_entry = $request->date_entry ?? $employee->date_entry;

            // Save the updated employee information
            $employee->save();

            return response()->json(['message' => 'Employee updated successfully!', 'employee' => $employee], 200);
        } catch (Exception $e) {
            Log::error('Employee modification failed: ' . $e->getMessage(), [
                'error' => $e,
            ]);

            return response()->json(['message' => 'Failed to modify employee.', 'error' => $e->getMessage()], 500);
        }
    }


    public function disable(Request $request, $id)
    {
        try {
            // Find the employee by ID
            $employee = Employee::findOrFail($id);

            // Update employee status to 'disabled'
            $employee->status = 'disabled';
            $employee->save();

            return response()->json(['message' => 'Employee disabled successfully!'], 200);
        } catch (Exception $e) {
            Log::error('Failed to disable employee: ' . $e->getMessage(), [
                'error' => $e,
            ]);

            return response()->json(['message' => 'Failed to disable employee.', 'error' => $e->getMessage()], 500);
        }
    }


    public function stat(Request $request)
    {
        try {
            // Calculate the number of employees
            $totalEmployees = Employee::count();

            // Calculate the number of employees per position
            $employeesPerPosition = Employee::select('id_position')
                ->groupBy('id_position')
                ->selectRaw('count(*) as count')
                ->get();

            // Calculate the number of active and disabled employees
            $activeEmployees = Employee::where('status', '!=', 'disabled')->count();
            $disabledEmployees = Employee::where('status', 'disabled')->count();

            // Return the statistics
            return response()->json([
                'total_employees' => $totalEmployees,
                'employees_per_position' => $employeesPerPosition,
                'active_employees' => $activeEmployees,
                'disabled_employees' => $disabledEmployees,
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to fetch employee statistics: ' . $e->getMessage(), [
                'error' => $e,
            ]);

            return response()->json(['message' => 'Failed to fetch statistics.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getMaxMatriculeForYear(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'year' => 'required|integer|digits:4',  // Ensure the provided year is a valid 4-digit integer
        ]);

        try {
            $year = $request->year;

            // Query the employees table to get the highest matricule for that year
            $lastMatricule = Employee::where('matricule', 'like', "{$year}-%")
                                ->orderBy('matricule', 'desc')
                                ->first();

            if ($lastMatricule) {
                return response()->json([
                    'message' => 'Success',
                    'max_matricule' => $lastMatricule->matricule,
                    'year' => $year,
                ], 200);
            } else {
                // No matricules found for the given year
                return response()->json([
                    'message' => 'No employees found for the given year.',
                    'max_matricule' => null,
                    'year' => $year,
                ], 404);
            }

        } catch (Exception $e) {
            // Log the error and return a response
            Log::error('Error retrieving matricule: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['message' => 'Error retrieving matricule.', 'error' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            // Récupérer les employés avec la table Position liée
            $employees = Employee::with('position') // Charger la relation position
                ->when($request->id_position, function ($query) use ($request) {
                    return $query->where('id_position', $request->id_position);
                })
                ->when($request->status, function ($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->get();
    
            // Retourner la liste des employés avec leurs positions
            return response()->json(['employees' => $employees], 200);
        } catch (Exception $e) {
            // Journaliser l'erreur et retourner une réponse
            Log::error('Failed to retrieve employees: ' . $e->getMessage(), [
                'error' => $e,
            ]);
    
            return response()->json(['message' => 'Failed to retrieve employees.', 'error' => $e->getMessage()], 500);
        }
    }    

}
