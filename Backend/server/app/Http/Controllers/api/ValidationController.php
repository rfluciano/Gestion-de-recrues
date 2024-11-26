<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Resource;
use App\Models\Request as RequestModel;
use Carbon\Carbon;

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

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_validator' => 'required|string|exists:useraccount,matricule',
            'id_request' => 'required|integer|exists:requests,id_request',
            'validation_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'status' => 'required|string|max:50',
            'rejection_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Create the validation
        $validation = Validation::create($request->all());

        // Find the request and associated resource
        $requestModel = RequestModel::find($request->id_request);
        if ($requestModel && $requestModel->id_resource) {
            $resource = Resource::find($requestModel->id_resource);
            
            if ($resource) {
                // Update resource fields
                $resource->isavailable = false;
                $resource->date_attribution = now();
                $resource->save();
            }
        }

        return response()->json(['message' => 'Validation created successfully!', 'validation' => $validation], 201);
    }

    // Update a specific validation
    public function update(Request $request, $id)
    {
        try {
            $validation = Validation::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'id_validator' => 'nullable|string|exists:useraccount,matricule',
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
