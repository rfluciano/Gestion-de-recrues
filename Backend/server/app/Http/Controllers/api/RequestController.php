<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Validation;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Assuming this is the correct model


class RequestController extends Controller
{
    public function create(Request $request)
    {
        try {
            $request->validate([
                'id_requester' => 'nullable|integer|exists:useraccount,id_user',
                'id_resource' => 'required|integer|exists:resources,id_resource',
                'id_receiver' => 'nullable|integer|exists:useraccount,id_user',
                'request_date' => 'nullable|date',
            ]);

            $newRequest = RequestModel::create([
                'id_requester' => $request->id_requester,
                'id_resource' => $request->id_resource,
                'id_receiver' => $request->id_receiver,
                'request_date' => $request->request_date,
            ]);

            return response()->json(['message' => 'Request created successfully!', 'request' => $newRequest], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create request: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create request.', 'error' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        try {
            $requests = RequestModel::with(['requester', 'resource', 'receiver', 'validation'])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($request) {
                    // Set validation to null if it's the default empty model
                    if ($request->validation && !$request->validation->exists) {
                        $request->validation = null;
                    }
                    return $request;
                });

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve requests: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve requests.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $request = RequestModel::with(['requester', 'resource', 'validation'])->findOrFail($id);

            // Set validation to null if it's the default empty model
            if ($request->validation && !$request->validation->exists) {
                $request->validation = null;
            }

            return response()->json($request);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve request: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve request.', 'error' => $e->getMessage()], 500);
        }
    }


    // Update an existing request
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'id_requester' => 'nullable|integer|exists:useraccount,id_user',
                'id_resource' => 'nullable|integer|exists:resources,id_resource',
                'id_receiver' => 'nullable|string',
                'delivery_date' => 'nullable|date',
                'request_date' => 'nullable|date',
            ]);

            $existingRequest = RequestModel::findOrFail($id);

            $existingRequest->update([
                'id_requester' => $request->id_requester ?? $existingRequest->id_requester,
                'id_resource' => $request->id_resource ?? $existingRequest->id_resource,
                'id_receiver' => $request->id_receiver ?? $existingRequest->id_receiver,
                'delivery_date' => $request->delivery_date ?? $existingRequest->delivery_date,
                'request_date' => $request->request_date ?? $existingRequest->request_date,
            ]);

            return response()->json(['message' => 'Request updated successfully!', 'request' => $existingRequest]);
        } catch (\Exception $e) {
            Log::error('Failed to update request: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update request.', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a request
    public function delete($id)
    {
        try {
            $request = RequestModel::findOrFail($id);
            $request->delete();

            return response()->json(['message' => 'Request deleted successfully!']);
        } catch (\Exception $e) {
            Log::error('Failed to delete request: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete request.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get requests by requester ID
    public function getByRequester($requesterId)
    {
        try {
            $requests = RequestModel::where('id_requester', $requesterId)
                ->with(['requester', 'resource', 'validation']) // Eager load validation
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve requests by requester: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve requests.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get requests sent by a specific requester
    public function getSentRequests($requesterId)
    {
        try {
            $requests = RequestModel::where('id_requester', $requesterId)
                ->with(['requester', 'resource', 'receiver', 'validation'])
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve sent requests: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve sent requests.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get requests received by a specific receiver
    public function getReceivedRequests($receiverId)
    {
        try {
            $requests = RequestModel::where('id_receiver', $receiverId)
                ->with(['requester', 'resource', 'receiver', 'validation'])
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve received requests: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve received requests.', 'error' => $e->getMessage()], 500);
        }
    }



    public function handleRequests(Request $request)
    {
        $requests = $request->input('requests'); // Array of resources with id_resource, id_receiver, id_requester
        $responses = [];
        
        foreach ($requests as $requestData) {
            $id_resource = $requestData['id_resource'];
            $id_receiver = $requestData['id_receiver'];
            $id_requester = $requestData['id_requester'];
            
            try {
                // Start a transaction
                DB::beginTransaction();
    
                // Check if id_receiver exists in the UserAccount table
                if (!User::where('id_user', $id_receiver)->exists()) {
                    throw new \Exception("Receiver ID $id_receiver does not exist in the UserAccount table.");
                }
    
                // Insert the request and get the auto-incremented id_request
                $id_request = DB::table('requests')->insertGetId([
                    'id_resource' => $id_resource,
                    'id_receiver' => $id_receiver,
                    'id_requester' => $id_requester,
                    'created_at' => now(),
                    'updated_at' => now()
                ], 'id_request'); // specify 'id_request' as the auto-increment column
    
                // Prepare the response data
                $responseData = [
                    'id_resource' => $id_resource,
                    'status' => 'success',
                    'message' => 'Request created successfully'
                ];
    
                // If self-approved (id_requester === id_receiver), create a validation record
                if ($id_requester === $id_receiver) {
                    Validation::create([
                        'id_validator' => $id_requester,
                        'id_request' => $id_request,
                        'status' => 'Approved',
                        'validation_date' => now(),
                        'delivery_date' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
    
                    $responseData['id_request'] = $id_request; // Add id_request to the response for reference
                }
    
                // Commit the transaction
                DB::commit();
    
                $responses[] = $responseData;
            } catch (\Exception $e) {
                // Rollback transaction if there is an error
                DB::rollBack();
    
                // Log the error details
                Log::error("Failed to create request for resource ID {$id_resource}", [
                    'id_resource' => $id_resource,
                    'id_receiver' => $id_receiver,
                    'id_requester' => $id_requester,
                    'error_message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
    
                $responses[] = [
                    'id_resource' => $id_resource,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return response()->json($responses);
    }    
    
}
