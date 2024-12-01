<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Resource;
use App\Models\Notification;
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

    public function approve(Request $request, $id_validation)
    {
        try {
            $validation = Validation::findOrFail($id_validation);
    
            $validator = Validator::make($request->all(), [
                'id_request' => 'required|integer|exists:requests,id_request',
                'id_validator' => 'required|string|exists:useraccount,matricule',
                'delivery_date' => 'required|date',
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
    
            // Update the validation
            $validation->update([
                'id_request' => $request->id_request,
                'id_validator' => $request->id_validator,
                'delivery_date' => $request->delivery_date,
                'validation_date' => now(),
                'status' => 'Approuvé',
                'rejection_reason' => null, // Clear the rejection reason if it's being approved
            ]);
    
            // Retrieve associated request and resource
            $requestModel = RequestModel::find($request->id_request);
            if ($requestModel && $requestModel->id_resource) {
                $resource = Resource::find($requestModel->id_resource);
    
                if ($resource) {
                    // Update the resource status
                    $resource->isavailable = 'Pris';
                    $resource->date_attribution = now();
                    $resource->id_holder = $requestModel->id_beneficiary ?? null;
                    $resource->save();
                }
            }
    
            // Notify the validator and requester
            $this->createNotification($request->id_validator, 'Validation approuvée avec succès.', [$request->id_request]);
            $this->createNotification($requestModel->id_requester, 'Votre requête de ressource a été approuvée.', [$request->id_request]);
    
            return response()->json(['message' => 'Validation approved successfully!', 'validation' => $validation], 200);
        } catch (\Exception $e) {
            Log::error('Failed to approve validation: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to approve validation.', 'error' => $e->getMessage()], 500);
        }
    }
    
    public function reject(Request $request, $id_validation)
    {
        try {
            $validation = Validation::findOrFail($id_validation);
    
            $validator = Validator::make($request->all(), [
                'id_request' => 'required|integer|exists:requests,id_request',
                'id_validator' => 'required|string|exists:useraccount,matricule',
                'rejection_reason' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
    
            // Update the validation
            $validation->update([
                'id_request' => $request->id_request,
                'id_validator' => $request->id_validator,
                'validation_date' => now(),
                'status' => 'Rejeté',
                'rejection_reason' => $request->rejection_reason,
                'delivery_date' => null, // Clear the delivery date if rejected
            ]);
    
            // Notify the validator and requester
            $this->createNotification($request->id_validator, 'Validation rejetée avec succès.', [$request->id_request]);
            $this->createNotification(RequestModel::find($request->id_request)->id_requester, 'Votre requête de ressource a été rejetée.', [$request->id_request]);
    
            return response()->json(['message' => 'Validation rejected successfully!', 'validation' => $validation], 200);
        } catch (\Exception $e) {
            Log::error('Failed to reject validation: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reject validation.', 'error' => $e->getMessage()], 500);
        }
    }
    

    private function createNotification($id_user, $message, $id_requests = [])
    {
        try {
            // Prepare the data payload
            $data = [
                'message' => $message,
                'count' => count($id_requests),
                'requests' => $id_requests,
            ];

            // Create a new notification entry
            Notification::create([
                'id_user' => $id_user,
                'event_type' => 'validation_update',
                'data' => json_encode($data),
                'message' => $message, // Explicitly set the message
                'resolved_at' => null, // Indicate that this notification is unresolved
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create notification for user $id_user: " . $e->getMessage());
        }
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
