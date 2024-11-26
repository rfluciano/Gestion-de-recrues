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

        // Créez la validation
        $validation = Validation::create($request->all());

        // Trouver la requête associée et la ressource concernée
        $requestModel = RequestModel::find($request->id_request);
        // Individual notifications for requester and receiver
        $this->createNotification($request->id_validator, 'Vous avez traité la requête avec succès.', [$request->id_validator]);
        $this->createNotification($requestModel->id_requester, 'Votre requête de ressource a été approvée avec succès.', [$requestModel->id_request]);
        
        if ($requestModel && $requestModel->id_resource) {
            $resource = Resource::find($requestModel->id_resource);

            if ($resource) {
                // Mettre à jour les champs de la ressource
                $resource->isavailable = 'Pris'; // La ressource est maintenant attribuée
                $resource->date_attribution = now();

                // Mettre à jour le détenteur de la ressource
                if (!empty($requestModel->id_beneficiary)) {
                    $resource->id_user_holder = $requestModel->id_beneficiary;
                }

                $resource->save();
            }
        }

        return response()->json(['message' => 'Validation created successfully!', 'validation' => $validation], 201);
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
