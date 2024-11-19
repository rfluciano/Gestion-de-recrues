<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unity;
use App\Models\Position;
use Illuminate\Support\Facades\Log;


class UnityController extends Controller
{
    // Create a new unity
    public function create(Request $request)
    {
        try {
            $request->validate([
                'id_parent' => 'nullable|integer|exists:unities,id_unity', // Ensure the parent exists if provided
                'type' => 'required|string|max:255',
                'title' => 'required|string|max:255',
            ]);

            $unity = Unity::create([
                'id_parent' => $request->id_parent,
                'type' => $request->type,
                'title' => $request->title,
            ]);

            return response()->json(['message' => 'Unity created successfully!', 'unity' => $unity], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create unity: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create unity.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get a list of all unities
    public function index()
    {
        $unities = Unity::all();
        return response()->json($unities);
    }

    // Update an existing unity
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'id_parent' => 'nullable|integer|exists:unities,id_unity', // Ensure the parent exists if provided
                'type' => 'sometimes|required|string|max:255',
                'title' => 'sometimes|required|string|max:255',
            ]);

            $unity = Unity::findOrFail($id);

            $unity->update([
                'id_parent' => $request->id_parent ?? $unity->id_parent,
                'type' => $request->type ?? $unity->type,
                'title' => $request->title ?? $unity->title,
            ]);

            return response()->json(['message' => 'Unity updated successfully!', 'unity' => $unity]);
        } catch (\Exception $e) {
            Log::error('Failed to update unity: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update unity.', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a unity
    public function delete($id)
    {
        try {
            $unity = Unity::findOrFail($id);
            $unity->delete();

            return response()->json(['message' => 'Unity deleted successfully!']);
        } catch (\Exception $e) {
            Log::error('Failed to delete unity: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete unity.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get a single unity by ID
    public function show($id)
    {
        try {
            $unity = Unity::findOrFail($id);
            return response()->json($unity);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve unity: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve unity.', 'error' => $e->getMessage()], 500);
        }
    }

    public function ids()
    {
        try {
            $unityIds = Unity::pluck('id_unity'); // Get all id_unity values

            return response()->json(['unity_ids' => $unityIds], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve unity ids: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve unity ids.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get all positions where id_unity matches a specific ID
    public function getPosition(Request $request, $id_unity)
    {
        try {
            // Validate that id_unity exists in the unities table
            $unity = Unity::findOrFail($id_unity);

            // Retrieve all positions where id_unity matches the provided id
            $positions = Position::where('id_unity', $id_unity)->get();

            return response()->json(['positions' => $positions], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve positions for unity: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve positions.', 'error' => $e->getMessage()], 500);
        }
    }
}
