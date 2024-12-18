<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unity;
use App\Models\Position;
use Illuminate\Support\Facades\Log;
use App\Events\MyEvent;


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
            event(new MyEvent('Position', 'created'));


            return response()->json(['message' => 'Unity created successfully!', 'unity' => $unity], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create unity: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create unity.', 'error' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        // Eager load the 'parent' relationship
        $unities = Unity::with('parent')->get();
    
        // Optionally format the data to include only the fields you want
        $formattedUnities = $unities->map(function ($unity) {
            return [
                'id_unity' => $unity->id_unity,
                'id_parent' => $unity->id_parent,
                'type' => $unity->type,
                'title' => $unity->title,
                'created_at' => $unity->created_at,
                'updated_at' => $unity->updated_at,
                'parent' => $unity->parent ? [
                    'id_unity' => $unity->parent->id_unity,
                    'type' => $unity->parent->type,
                    'title' => $unity->parent->title,
                ] : null, // Include parent details or null if no parent
            ];
        });
    
        return response()->json($formattedUnities);
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

            event(new MyEvent('Unity', 'modified'));


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
            event(new MyEvent('Unity', 'deleted'));

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

        // Search unities by type, title, or other fields
    public function search(Request $request)
    {
        try {
            // Validate search inputs
            $request->validate([
                'type' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
            ]);

            // Build the query
            $query = Unity::query();

            if ($request->filled('type')) {
                $query->where('type', 'like', '%' . $request->type . '%');
            }

            if ($request->filled('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            // Execute the query and fetch results
            $results = $query->get();

            return response()->json(['message' => 'Search completed successfully!', 'results' => $results]);
        } catch (\Exception $e) {
            Log::error('Search failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to complete search.', 'error' => $e->getMessage()], 500);
        }
    }

    // Advanced search including parent relationships
    public function advancedSearch(Request $request)
    {
        try {
            // Validate inputs
            $request->validate([
                'type' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
                'parent_title' => 'nullable|string|max:255',
            ]);

            // Build the query with parent relationships
            $query = Unity::query()->with('parent');

            if ($request->filled('type')) {
                $query->where('type', 'like', '%' . $request->type . '%');
            }

            if ($request->filled('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            if ($request->filled('parent_title')) {
                $query->whereHas('parent', function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->parent_title . '%');
                });
            }

            // Execute the query and fetch results
            $results = $query->get();

            return response()->json(['message' => 'Advanced search completed successfully!', 'results' => $results]);
        } catch (\Exception $e) {
            Log::error('Advanced search failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to complete advanced search.', 'error' => $e->getMessage()], 500);
        }
    }

}
