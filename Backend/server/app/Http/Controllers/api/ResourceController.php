<?php

namespace App\Http\Controllers\Api;

use App\Models\Resource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;


class ResourceController extends Controller
{
    // Retrieve all resources
    public function index()
    {
        $resources = Resource::with(['holder', 'chief'])->get();
        return response()->json($resources);
    }

    // Function to get weekly, monthly, and yearly counts of resources with id_user_holder
    public function resourceCounts()
    {
        try {
            // Weekly resource count for the current week (Monday to Sunday)
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();

            // Fix: Ensure we use a valid alias for the count field
            $weeklyCounts = Resource::select(DB::raw('DATE(date_attribution) as date'), DB::raw('COUNT(*) as resource_count'))
                ->whereNotNull('id_user_holder')
                ->whereBetween('date_attribution', [$startOfWeek, $endOfWeek])
                ->groupBy(DB::raw('DATE(date_attribution)'))
                ->get()
                ->mapWithKeys(function ($item) {
                    // Convert date to a weekday name (e.g., 'Mon' or 'Lundi')
                    $dayName = Carbon::parse($item->date)->locale('fr')->isoFormat('ddd'); // French short day name
                    return ["$dayName: {$item->resource_count}"];
                });

            // Monthly resource count for the current year (January to December)
            $startOfYear = Carbon::now()->startOfYear();
            $endOfYear = Carbon::now()->endOfYear();

            $monthlyCounts = Resource::select(DB::raw("TO_CHAR(date_attribution, 'Month') as month"), DB::raw('COUNT(*) as resource_count'))
                ->whereNotNull('id_user_holder')
                ->whereBetween('date_attribution', [$startOfYear, $endOfYear])
                ->groupBy(DB::raw("TO_CHAR(date_attribution, 'Month')"))
                ->get()
                ->mapWithKeys(function ($item) {
                    return [trim($item->month) => $item->resource_count];
                });

            // Yearly resource count for each year
            $yearlyCounts = Resource::select(DB::raw("EXTRACT(YEAR FROM date_attribution) as year"), DB::raw('COUNT(*) as resource_count'))
                ->whereNotNull('id_user_holder')
                ->groupBy(DB::raw("EXTRACT(YEAR FROM date_attribution)"))
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
            Log::error('Failed to fetch resource counts: ' . $e->getMessage(), [
                'error' => $e,
            ]);

            return response()->json(['message' => 'Failed to fetch resource counts.', 'error' => $e->getMessage()], 500);
        }
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
            'access_login' => 'nullable|string|max:255',
            'access_password' => 'nullable|string|max:255',
            'discriminator' => 'required|string',
            'isavailable' => 'required|boolean',
            'description' => 'nullable|string|max:255'
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
            'resources.*.id_user_holder' => 'nullable|exists:useraccount,id_user',
            'resources.*.id_user_chief' => 'required|exists:useraccount,id_user',
            'resources.*.label' => 'required|string|max:255',
            'resources.*.access_login' => 'nullable|string|max:255',
            'resources.*.access_password' => 'nullable|string|max:255',
            'resources.*.discriminator' => 'required|string',
            'resources.*description' => 'nullable|string|max:255',
            'resources.*.isavailable' => 'required|boolean'
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

    // Retrieve all resources managed by a specific chief
    public function getResourcesByChief($chiefId)
    {
        try {
            $resources = Resource::where('id_user_chief', $chiefId)
                ->with(['holder', 'chief'])
                ->get();

            return response()->json($resources);
        } catch (Exception $e) {
            Log::error('Failed to retrieve resources by chief: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve resources by chief.', 'error' => $e->getMessage()], 500);
        }
    }
}