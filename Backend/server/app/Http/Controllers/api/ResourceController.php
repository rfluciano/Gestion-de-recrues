<?php

namespace App\Http\Controllers\Api;

use App\Models\Resource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class ResourceController extends Controller
{
    // Retrieve all resources
    public function index()
    {
        try {
            $resources = Resource::with(['holder', 'chief'])->get();
            return response()->json($resources, 200);
        } catch (Exception $e) {
            Log::error('Failed to retrieve resources: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve resources.', 'error' => $e->getMessage()], 500);
        }
    }

    // Retrieve weekly, monthly, and yearly counts of resources with id_user_holder
    public function resourceCounts()
    {
        try {
            // Weekly resource count for the current week
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();

            $weeklyCounts = Resource::select(DB::raw('DATE(date_attribution) as date'), DB::raw('COUNT(*) as resource_count'))
                ->whereNotNull('id_user_holder')
                ->whereBetween('date_attribution', [$startOfWeek, $endOfWeek])
                ->groupBy(DB::raw('DATE(date_attribution)'))
                ->get()
                ->mapWithKeys(function ($item) {
                    $dayName = Carbon::parse($item->date)->locale('fr')->isoFormat('ddd'); // French short day name
                    return [$dayName => $item->resource_count];
                });

            // Monthly resource count for the current year
            $startOfYear = Carbon::now()->startOfYear();
            $endOfYear = Carbon::now()->endOfYear();

            $monthlyCounts = Resource::select(DB::raw("MONTHNAME(date_attribution) as month"), DB::raw('COUNT(*) as resource_count'))
                ->whereNotNull('id_user_holder')
                ->whereBetween('date_attribution', [$startOfYear, $endOfYear])
                ->groupBy(DB::raw("MONTHNAME(date_attribution)"))
                ->get()
                ->mapWithKeys(function ($item) {
                    return [trim($item->month) => $item->resource_count];
                });

            // Yearly resource count
            $yearlyCounts = Resource::select(DB::raw("YEAR(date_attribution) as year"), DB::raw('COUNT(*) as resource_count'))
                ->whereNotNull('id_user_holder')
                ->groupBy(DB::raw("YEAR(date_attribution)"))
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->year => $item->resource_count];
                });

            return response()->json([
                'weekly_counts' => $weeklyCounts,
                'monthly_counts' => $monthlyCounts,
                'yearly_counts' => $yearlyCounts,
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to fetch resource counts: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch resource counts.', 'error' => $e->getMessage()], 500);
        }
    }

    // Retrieve a specific resource by ID
    public function show($id)
    {
        try {
            $resource = Resource::with(['holder', 'chief'])->findOrFail($id);
            return response()->json($resource, 200);
        } catch (Exception $e) {
            Log::error('Failed to retrieve resource: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve resource.', 'error' => $e->getMessage()], 500);
        }
    }

    // Store a new resource
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_user_holder' => 'nullable|exists:useraccount,matricule',
            'id_user_chief' => 'required|exists:useraccount,matricule',
            'label' => 'required|string|max:255',
            'discriminator' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Définir automatiquement isavailable en fonction de id_user_holder
            $data = $request->all();
            $data['isavailable'] = $request->input('id_user_holder') ? 'Pris' : 'Libre';

            $resource = Resource::create($data);
            return response()->json(['message' => 'Resource created successfully.', 'resource' => $resource], 201);
        } catch (Exception $e) {
            Log::error('Failed to create resource: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create resource.', 'error' => $e->getMessage()], 500);
        }
    }


        // Update a specific resource
        public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_user_holder' => 'nullable|exists:useraccount,matricule',
            'id_user_chief' => 'required|exists:useraccount,matricule',
            'label' => 'required|string|max:255',
            'discriminator' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $resource = Resource::findOrFail($id);

            // Définir automatiquement isavailable en fonction de id_user_holder
            $data = $request->all();
            if (array_key_exists('id_user_holder', $data)) {
                $data['isavailable'] = $data['id_user_holder'] ? false : true;
            }

            $resource->update($data);
            return response()->json(['message' => 'Resource updated successfully.', 'resource' => $resource], 200);
        } catch (Exception $e) {
            Log::error('Failed to update resource: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update resource.', 'error' => $e->getMessage()], 500);
        }
    }


    // Delete a specific resource
    public function destroy($id)
    {
        try {
            $resource = Resource::findOrFail($id);
            $resource->delete();
            return response()->json(['message' => 'Resource deleted successfully.'], 200);
        } catch (Exception $e) {
            Log::error('Failed to delete resource: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete resource.', 'error' => $e->getMessage()], 500);
        }
    }

    // Import function for bulk resource creation
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resources' => 'required|array',
            'resources.*.id_user_holder' => 'nullable|exists:useraccount,matricule',
            'resources.*.id_user_chief' => 'required|exists:useraccount,matricule',
            'resources.*.label' => 'required|string|max:255',
            'resources.*.discriminator' => 'required|string',
            'resources.*.isavailable' => 'required|boolean',
            'resources.*.description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $resources = $request->input('resources');
            $createdResources = Resource::insert($resources);
            return response()->json(['message' => 'Resources imported successfully.', 'data' => $createdResources], 201);
        } catch (Exception $e) {
            Log::error('Failed to import resources: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to import resources.', 'error' => $e->getMessage()], 500);
        }
    }

    // Retrieve resources managed by a specific chief
    public function getResourcesByChief($chiefId)
    {
        try {
            $resources = Resource::where('id_user_chief', $chiefId)
                ->with(['holder', 'chief'])
                ->get();
            return response()->json($resources, 200);
        } catch (Exception $e) {
            Log::error('Failed to retrieve resources by chief: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve resources.', 'error' => $e->getMessage()], 500);
        }
    }
    public function getAvailableResources()
    {
        try {
            $resources = DB::table('resources')
            ->where('isavailable', "Libre")
            ->get();
 
            return response()->json($resources, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve resources: ' . $e->getMessage());
    
            return response()->json([
                'message' => 'Failed to retrieve resources.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    
}
