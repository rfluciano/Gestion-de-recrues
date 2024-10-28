<?php

namespace App\Http\Controllers\Api;

use App\Models\Resource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{
    // Retrieve all resources
    public function index()
    {
        $resources = Resource::with(['holder', 'chief'])->get();
        return response()->json($resources);
    }

    // Retrieve a specific resource by ID
    public function show($id)
    {
        $resource = Resource::with(['holder', 'chief'])->findOrFail($id);
        return response()->json($resource);
    }

    // Store a new resource
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_user_holder' => 'nullable|exists:useraccount,id_user',
            'id_user_chief' => 'required|exists:useraccount,id_user',
            'label' => 'required|string|max:255',
            'access_login' => 'required|string|max:255',
            'access_password' => 'required|string|max:255',
            'discriminator' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $resource = Resource::create($request->all());

        return response()->json($resource, 201);
    }

    // Update a specific resource
    public function update(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);
        $resource->update($request->all());

        return response()->json($resource);
    }

    // Delete a specific resource
    public function destroy($id)
    {
        $resource = Resource::findOrFail($id);
        $resource->delete();

        return response()->json(['message' => 'Resource deleted successfully.']);
    }

    // Import function for bulk data handling
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resources' => 'required|array',
            'resources.*.id_user_holder' => 'required|exists:useraccount,id_user',
            'resources.*.id_user_chief' => 'required|exists:useraccount,id_user',
            'resources.*.label' => 'required|string|max:255',
            'resources.*.access_login' => 'required|string|max:255',
            'resources.*.access_password' => 'required|string|max:255',
            'resources.*.discriminator' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $resources = $request->input('resources');
        $createdResources = [];

        foreach ($resources as $data) {
            $createdResources[] = Resource::create($data);
        }

        return response()->json(['message' => 'Resources imported successfully.', 'data' => $createdResources]);
    }
}