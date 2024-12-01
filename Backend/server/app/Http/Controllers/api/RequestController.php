<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Validation;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Notification;
use App\Events\MyEvent;

 // Assuming this is the correct model


class RequestController extends Controller
{
    public function create(Request $request)
    {
        
        try {
            // Log the incoming request data
            Log::info('Request data received: ', $request->all());

            $request->validate([
                'id_requester' => 'nullable|string|exists:useraccount,matricule',
                'id_resource' => [
                    'required',
                    'integer',
                    'unique:requests,id_resource',
                    'exists:resources,id_resource',
                ],
                'id_beneficiary' => 'required|string|exists:employees,matricule',
                'request_date' => 'nullable|date',
            ]);

            $resource = Resource::find($request->id_resource);

            // Debugging: Log resource retrieval
            if (!$resource) {
                Log::error('Resource not found for id: ' . $request->id_resource);
                return response()->json(['message' => 'Resource not found.'], 404);
            }

            $id_receiver = $resource->id_user_chief;
            $id_requester = $request->id_requester;

            // Log requester and receiver IDs
            Log::info("id_requester: $id_requester, id_receiver: $id_receiver");

            // Create the request
            $newRequest = RequestModel::create([
                'id_requester' => $id_requester,
                'id_resource' => $request->id_resource,
                'id_beneficiary' => $request->id_beneficiary,
                'id_receiver' => $id_receiver,
                'request_date' => $request->request_date,
            ]);

            // Debugging: Log request creation
            if (!$newRequest) {
                Log::error('Failed to create request in the database.');
                return response()->json(['message' => 'Failed to create request in the database.'], 500);
            }

            Log::info('Request created successfully: ', $newRequest->toArray());

            $validation_date = now();
            $validationStatus = ($id_receiver === $id_requester) ? 'Approved' : 'En attente';

            // Create the validation
            $validation = Validation::create([
                'id_validator' => $id_receiver,
                'id_request' => $newRequest->id_request,
                'status' => $validationStatus,
                'validation_date' => $validationStatus === 'Approved' ? $validation_date : null,
                'delivery_date' => $validationStatus === 'Approved' ? $validation_date : null,
                'rejection_reason' => null,
            ]);

            // Debugging: Log validation creation
            if (!$validation) {
                Log::error('Failed to create validation for request ID: ' . $newRequest->id_request);
                return response()->json(['message' => 'Failed to create validation.'], 500);
            }

            Log::info('Validation created successfully: ', $validation->toArray());

            // Update the availability of the resource
            $resource->isavailable = 'Pend';
            if (!$resource->save()) {
                Log::error('Failed to update resource availability for ID: ' . $request->id_resource);
                return response()->json(['message' => 'Failed to update resource availability.'], 500);
            }

            Log::info('Resource availability updated successfully for ID: ' . $request->id_resource);

            // Send notifications
            $this->createNotification($id_receiver, 'Vous avez une nouvelle requête en attente.', [$newRequest->id_request]);
            if ($id_requester) {
                $this->createNotification($id_requester, 'Votre requête de ressource a été créée avec succès.', [$newRequest->id_request]);
            }

            event(new MyEvent('Request', 'created'));
            return response()->json(['message' => 'Request created successfully!', 'request' => $newRequest], 201);

        } catch (\Exception $e) {
            Log::error('Exception during request creation: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to create request.', 'error' => $e->getMessage()], 500);
        }
    }

    
    



    private function createNotification($id_user, $message, $id_requests = [])
    {
        try {
            // Prepare the data payload
            $data = [
                'message' => $message,
                'count' => count($id_requests),
                'requests' => $id_requests,
            ];

            // Create a new notification entry
            Notification::create([
                'id_user' => $id_user,
                'event_type' => 'request_update',
                'data' => json_encode($data),
                'message' => $message, // Explicitly set the message
                'resolved_at' => null, // Indicate that this notification is unresolved
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create notification for user $id_user: " . $e->getMessage());
        }
    }

    

    public function handleRequests(Request $request)
    {
        $requests = $request->input('requests'); // Tableau des ressources avec id_resource, id_requester, id_beneficiary
        $responses = [];
    
        Log::info('Received requests payload', ['requests' => $requests]);
    
        foreach ($requests as $requestData) {
            $id_resource = $requestData['id_resource'];
            $id_requester = $requestData['id_requester'];
            $id_beneficiary = $requestData['id_beneficiary'];
    
            try {
                DB::beginTransaction();
    
                // Vérifier la ressource
                $resource = Resource::find($id_resource);
                if (!$resource || !$resource->id_user_chief) {
                    throw new \Exception("Resource with ID $id_resource is invalid or has no associated receiver.");
                }
    
                $id_receiver = $resource->id_user_chief;
                if (!$id_beneficiary) {
                    throw new \Exception("A beneficiary must be specified for the request.");
                }
    
                // Créer la requête
                $newRequest = RequestModel::create([
                    'id_resource' => $id_resource,
                    'id_receiver' => $id_receiver,
                    'id_requester' => $id_requester,
                    'id_beneficiary' => $id_beneficiary,
                    'request_date' => now(),
                ]);

                
    
                Log::info('Request created', ['id_request' => $newRequest->id_request]);
    
                // Mettre à jour la disponibilité de la ressource
                $resource->isavailable = 'Pend';
                $resource->save();
    
                // Validation automatique si le demandeur est également le destinataire
                if ($id_requester === $id_receiver) {
                    Validation::create([
                        'id_validator' => $id_requester,
                        'id_request' => $newRequest->id_request,
                        'status' => 'Approved',
                        'validation_date' => now(),
                        'delivery_date' => now(),
                    ]);
                    $resource->isavailable = 'Pend';
                    $resource->save();
                } else {
                    Validation::create([
                        'id_validator' => $id_receiver,
                        'id_request' => $newRequest->id_request,
                        'status' => 'En attente',
                        'validation_date' => now(),
                        'delivery_date' => null,
                        'rejection_reason' => null,
                    ]);
                    $resource->isavailable = 'Pris';
                    // $resource->id_ = 'Pris';
                    $resource->save();
                    //$id_beneficiary->update his isequipped into true
                }
                
    
                // Notifications individuelles
                $this->createNotification($id_receiver, 'Vous avez une nouvelle requête en attente.', [$newRequest->id_request]);
                $this->createNotification($id_requester, 'Votre requête de ressource a été créée avec succès.', [$newRequest->id_request]);
    
                DB::commit();
                event(new MyEvent('Request', 'created'));
                event(new MyEvent('Resource', 'Modified'));
                event(new MyEvent('Notification', 'created'));
                $responses[] = [
                    'id_resource' => $id_resource,
                    'status' => 'success',
                    'message' => 'Request created successfully',
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to create request for resource ID {$id_resource}", [
                    'id_resource' => $id_resource,
                    'error_message' => $e->getMessage(),
                ]);
                $responses[] = [
                    'id_resource' => $id_resource,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }
    
        return response()->json($responses);
    }    
     


    private function createOrUpdateNotification($id_user, $defaultMessage, $id_request = null)
    {
        try {
            // Check if there's an existing notification for the user and event
            $existingNotification = Notification::where('id_user', $id_user)
                ->where('event_type', 'request_update')
                ->whereNull('resolved_at') // To group unresolved notifications
                ->first();
    
            if ($existingNotification) {
                // Aggregate the notification
                $existingData = json_decode($existingNotification->data, true);
                $count = $existingData['count'] ?? 1;
                $count++;
                
                $existingRequests = $existingData['requests'] ?? [];
                if ($id_request) {
                    $existingRequests[] = $id_request; // Add the new request to the list
                }
    
                $existingNotification->data = json_encode([
                    'message' => $defaultMessage,
                    'count' => $count,
                    'requests' => $existingRequests,
                ]);
                $existingNotification->message = "$defaultMessage ($count requêtes)"; // Update the message
                $existingNotification->save();
            } else {
                // Create a new notification
                $data = [
                    'message' => $defaultMessage,
                    'count' => 1,
                ];
                if ($id_request) {
                    $data['requests'] = [$id_request]; // Include the single request
                }
    
                Notification::create([
                    'id_user' => $id_user,
                    'event_type' => 'request_update',
                    'data' => json_encode($data),
                    'message' => $defaultMessage, // Explicitly set the message
                    'resolved_at' => null, // Keep this notification unresolved initially
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to create or update notification for user $id_user: " . $e->getMessage());
        }
    }
      


    public function index()
    {
        try {
            $requests = RequestModel::with(['requester', 'resource', 'receiver', 'validation', 'beneficiary'])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($request) {
                    // Vérifier et convertir matricule en chaîne de caractères
                    $request->requester->matricule = (string) $request->requester->matricule;
                    $request->receiver->matricule = (string) $request->receiver->matricule;
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
            $request = RequestModel::with(['requester', 'resource', 'validation', 'receiver', 'beneficiary'])->findOrFail($id);

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
                'id_requester' => 'nullable|string|exists:useraccount,matricule',
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
                ->with(['requester', 'resource', 'receiver', 'validation', 'beneficiary'])
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
                ->with(['requester', 'resource', 'receiver', 'validation', 'beneficiary'])
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve received requests: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve received requests.', 'error' => $e->getMessage()], 500);
        }
    }

    
    // Add this method to the RequestController class
    public function search(Request $request)
    {
        try {
            $query = $request->input('query');

            // Validate query input
            if (!$query) {
                try {
                    $requests = RequestModel::with(['requester', 'resource', 'receiver', 'validation', 'beneficiary'])
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

            // Convert query to lowercase
            $query = strtolower($query);

            // Search logic
            $results = RequestModel::with(['requester', 'resource', 'receiver', 'validation', 'beneficiary'])
                ->where(function ($q) use ($query) {
                    $q->whereRaw('LOWER(CAST(id_request AS TEXT)) LIKE ?', ["%$query%"])
                        ->orWhereHas('resource', function ($resourceQuery) use ($query) {
                            $resourceQuery->whereRaw('LOWER(label) LIKE ?', ["%$query%"]);
                        })
                        ->orWhereHas('receiver', function ($receiverQuery) use ($query) {
                            $receiverQuery->whereRaw('LOWER(matricule) LIKE ?', ["%$query%"]);
                        })
                        ->orWhereHas('validation', function ($validationQuery) use ($query) {
                            $validationQuery->whereRaw('LOWER(status) LIKE ?', ["%$query%"])
                                ->orWhereRaw('LOWER(rejection_reason) LIKE ?', ["%$query%"])
                                ->orWhereRaw("CAST(validation_date AS TEXT) LIKE ?", ["%$query%"])
                                ->orWhereRaw("CAST(delivery_date AS TEXT) LIKE ?", ["%$query%"]);
                        });
                })
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json($results, 200);
        } catch (\Exception $e) {
            Log::error('Failed to perform search in requests table: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to perform search.', 'error' => $e->getMessage()], 500);
        }
    }

    public function filterRequests(Request $request)
    {
        try {
            // Initialisez la requête pour inclure les relations nécessaires
            $query = RequestModel::with(['validation', 'requester', 'resource', 'receiver', 'beneficiary']);

            // Ajoutez un filtre par statut de validation si fourni
            if ($request->has('status')) {
                $status = $request->input('status');
                $query->whereHas('validation', function ($validationQuery) use ($status) {
                    $validationQuery->where('status', $status);
                });
            }

            // Filtre par plage de dates (created_at)
            if ($request->has(['start_date', 'end_date'])) {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // Filtre par plage de dates (validation_date)
            if ($request->has(['validation_start_date', 'validation_end_date'])) {
                $validationStartDate = Carbon::parse($request->input('validation_start_date'))->startOfDay();
                $validationEndDate = Carbon::parse($request->input('validation_end_date'))->endOfDay();

                $query->whereHas('validation', function ($validationQuery) use ($validationStartDate, $validationEndDate) {
                    $validationQuery->whereBetween('validation_date', [$validationStartDate, $validationEndDate]);
                });
            }

            // Filtre personnalisé (par exemple, recherche par ID de ressource ou matricule)
            if ($request->has('search')) {
                $search = strtolower($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(CAST(id_request AS TEXT)) LIKE ?', ["%$search%"])
                    ->orWhereHas('resource', function ($resourceQuery) use ($search) {
                        $resourceQuery->whereRaw('LOWER(label) LIKE ?', ["%$search%"]);
                    })
                    ->orWhereHas('requester', function ($requesterQuery) use ($search) {
                        $requesterQuery->whereRaw('LOWER(matricule) LIKE ?', ["%$search%"]);
                    });
                });
            }

            // Trier les résultats par date de mise à jour par défaut
            $requests = $query->orderBy('updated_at', 'desc')->get();

            return response()->json($requests, 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors du filtrage des requêtes : ' . $e->getMessage());
            return response()->json(['message' => 'Une erreur s\'est produite lors du filtrage des requêtes.', 'error' => $e->getMessage()], 500);
        }
    }

}
