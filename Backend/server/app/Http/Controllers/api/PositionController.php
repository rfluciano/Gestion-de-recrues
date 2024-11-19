<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Position;
use Illuminate\Support\Facades\Log;

class PositionController extends Controller
{
    // Create a new position
    public function create(Request $request)
    {
        try {
            $request->validate([
                'id_unity' => 'required|integer|exists:unities,id_unity', // Ensure the unity exists
                'title' => 'required|string|max:255',
                'isavailable' => 'required|boolean',
            ]);

            $position = Position::create([
                'id_unity' => $request->id_unity,
                'title' => $request->title,
                'isavailable' => $request->isavailable, // Corrected here
            ]);            

            return response()->json(['message' => 'Position created successfully!', 'position' => $position], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create position: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create position.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get a list of all positions
    public function index()
    {
        $positions = Position::all();
        return response()->json($positions);
    }

    // Update an existing position
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'id_unity' => 'sometimes|required|integer|exists:unities,id_unity',
                'title' => 'sometimes|required|string|max:255',
                'isavailable' => 'required|boolean',
            ]);

            $position = Position::findOrFail($id);

            $position->update([
                'id_unity' => $request->id_unity ?? $position->id_unity,
                'title' => $request->title ?? $position->title,
                'isavailable' => $request->isavailable ?? $position->isavailable,

            ]);

            return response()->json(['message' => 'Position updated successfully!', 'position' => $position]);
        } catch (\Exception $e) {
            Log::error('Failed to update position: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update position.', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a position
    public function delete($id)
    {
        try {
            $position = Position::findOrFail($id);
            $position->delete();

            return response()->json(['message' => 'Position deleted successfully!']);
        } catch (\Exception $e) {
            Log::error('Failed to delete position: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete position.', 'error' => $e->getMessage()], 500);
        }
    }

    // Get a single position by ID
    public function show($id)
    {
        try {
            $position = Position::findOrFail($id);
            return response()->json($position);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve position: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve position.', 'error' => $e->getMessage()], 500);
        }
    }
}
