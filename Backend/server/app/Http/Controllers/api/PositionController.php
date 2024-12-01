<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Position;
use Illuminate\Support\Facades\Log;
use App\Events\MyEvent;

class PositionController extends Controller
{
    // Create a new position
public function create(Request $request)
{
    try {
        $request->validate([
            'id_unity' => 'required|integer|exists:unities,id_unity', // Ensure the unity exists
            'title' => 'required|string|max:255',
        ]);

        $position = Position::create([
            'id_unity' => $request->id_unity,
            'title' => $request->title, // Corrected: Added missing comma
            'isavailable' => true, // Initialize isavailable to true
        ]);

        event(new MyEvent('Position', 'created'));
        // event(new MyEvent('Notification', 'created'));
        return response()->json([
            'message' => 'Position created successfully!',
            'position' => $position
        ], 201);

        

    } catch (\Exception $e) {
        Log::error('Failed to create position: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to create position.',
            'error' => $e->getMessage()
        ], 500);
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
            event(new MyEvent('Position', 'deleted'));
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


    // Add this method to the PositionController class
    public function search(Request $request)
    {
        try {
            // Ensure a search query is provided
            $query = $request->input('query');
            if (!$query) {
                return response()->json(['message' => 'No search query provided.'], 400);
            }

            // Perform a search across all columns
            $positions = Position::where(function ($q) use ($query) {
                    $q->where('id_position', 'like', "%$query%")
                    ->orWhere('id_unity', 'like', "%$query%")
                    ->orWhere('title', 'like', "%$query%")
                    ->orWhere('isavailable', 'like', "%$query%")
                    ->orWhereRaw("CAST(created_at AS CHAR) LIKE ?", ["%$query%"])
                    ->orWhereRaw("CAST(updated_at AS CHAR) LIKE ?", ["%$query%"]);
                })
                ->get();

            // Return the results
            return response()->json(['positions' => $positions], 200);
        } catch (\Exception $e) {
            Log::error('Failed to perform search: ' . $e->getMessage(), [
                'query' => $request->input('query'),
                'error' => $e,
            ]);

            return response()->json(['message' => 'Failed to perform search.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAvailablePosition(Request $request)
    {
        try {
            // Récupérer toutes les positions disponibles
            $availablePositions = Position::where('isavailable', true)->get();
    
            // Vérifier s'il y a des résultats
            if ($availablePositions->isEmpty()) {
                return response()->json([
                    'position' => 'Aucun poste disponible'
                ], 404);
            }
    
            // Retourner les positions disponibles
            return response()->json([
                'message' => 'Positions disponibles récupérées avec succès.',
                'positions' => $availablePositions
            ], 200);
        } catch (\Exception $e) {
            // Gérer les erreurs
            Log::error('Erreur lors de la récupération des positions disponibles : ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la récupération des positions disponibles.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

}
