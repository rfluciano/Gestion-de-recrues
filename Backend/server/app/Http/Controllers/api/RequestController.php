<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    public function create(Request $request)
    {
        try {
            $request->validate([
                'id_requester' => 'nullable|integer|exists:useraccount,id_user',
                'id_resource' => 'required|integer|exists:resources,id_resource',
                'id_receiver' => 'required|integer',
                'delivery_date' => 'nullable|date',
                'request_date' => 'nullable|date',
            ]);

            $newRequest = RequestModel::create([
                'id_requester' => $request->id_requester,
                'id_resource' => $request->id_resource,
                'id_receiver' => $request->id_receiver,
                'delivery_date' => $request->delivery_date,
                'request_date' => $request->request_date,
            ]);

            return response()->json(['message' => 'Request created successfully!', 'request' => $newRequest], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create request: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create request.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get all requests with validation if exists
    public function index()
    {
        try {
            $requests = RequestModel::with(['requester', 'resource'])
                ->get()
                ->map(function ($request) {
                    $validation = Validation::where('id_request', $request->id)->first();
                    $request->validation = $validation ?: null;
                    return $request;
                });

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve requests: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve requests.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get a single request by ID with validation if exists
    public function show($id)
    {
        try {
            $request = RequestModel::with(['requester', 'resource'])->findOrFail($id);
            $validation = Validation::where('id_request', $id)->first();
            $request->validation = $validation ?: null;

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
            $requests = RequestModel::where('id_requester', $requesterId)->with('requester', 'resource')->get();
            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve requests by requester: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve requests.', 'error' => $e->getMessage()], 500);
        }
    }
}
