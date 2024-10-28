<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ValidationController extends Controller
{
    // Retrieve all validations
    public function index()
    {
        $validations = Validation::with(['validator', 'request'])->get();
        return response()->json($validations);
    }

    // Retrieve a specific validation by ID
    public function show($id)
    {
        try {
            $validation = Validation::with(['validator', 'request'])->findOrFail($id);
            return response()->json($validation);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve validation: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve validation.', 'error' => $e->getMessage()], 500);
        }
    }

    // Store a new validation
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_validator' => 'required|integer|exists:useraccount,id_user',
            'id_request' => 'required|integer|exists:requests,id_request',
            'validation_date' => 'nullable|date',
            'status' => 'required|string|max:50',
            'rejection_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $validation = Validation::create($request->all());
        return response()->json(['message' => 'Validation created successfully!', 'validation' => $validation], 201);
    }

    // Update a specific validation
    public function update(Request $request, $id)
    {
        try {
            $validation = Validation::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'id_validator' => 'nullable|integer|exists:useraccount,id_user',
                'id_request' => 'nullable|integer|exists:requests,id_request',
                'validation_date' => 'nullable|date',
                'status' => 'nullable|string|max:50',
                'rejection_reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $validation->update($request->all());
            return response()->json(['message' => 'Validation updated successfully!', 'validation' => $validation]);
        } catch (\Exception $e) {
            Log::error('Failed to update validation: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update validation.', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a specific validation
    public function delete($id)
    {
        try {
            $validation = Validation::findOrFail($id);
            $validation->delete();

            return response()->json(['message' => 'Validation deleted successfully!']);
        } catch (\Exception $e) {
            Log::error('Failed to delete validation: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete validation.', 'error' => $e->getMessage()], 500);
        }
    }
}
