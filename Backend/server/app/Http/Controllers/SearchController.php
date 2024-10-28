<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    // Universal search function
    public function universalSearch(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1', // Ensure there's a query to search for
        ]);

        $query = $request->query('query');
        $results = [];

        // Define the tables to be searched (excluding system tables like 'migrations')
        $searchableTables = ['employees', 'useraccount', 'positions', 'resources', 'unities','requests','validations'];

        foreach ($searchableTables as $tableName) {
            $columns = Schema::getColumnListing($tableName);
            $tableQuery = DB::table($tableName);

            foreach ($columns as $column) {
                $columnType = Schema::getColumnType($tableName, $column);

                // Search in text-like columns (e.g., names, titles)
                if (in_array($columnType, ['string', 'text'])) {
                    $tableQuery->orWhere($column, 'like', '%' . $query . '%');
                }
            }

            $tableResults = $tableQuery->get();
            if (!$tableResults->isEmpty()) {
                $results[$tableName] = $tableResults;
            }
        }

        return count($results) > 0
            ? response()->json(['results' => $results], 200)
            : response()->json(['message' => 'No results found.'], 404);
    }
}
