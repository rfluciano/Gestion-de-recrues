<?php

use App\Events\MyEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PusherController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\UnityController;
use App\Http\Controllers\Api\ValidationController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\NotificationController;


//------------------NOTIFICATION-------------------//

Route::post('/notifications', [NotificationController::class, 'store']);
Route::get('/notifications/{id_user}', [NotificationController::class, 'index']);
Route::put('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
Route::put('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);


//------------------GOATS-------------------//

Route::get('/', function () {
    return 'API';
});
Route::get('/search', [SearchController::class, 'universalSearch']);


//------------------AUTHENTIFICATION-------------------//
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::group(['middleware' => ['auth:api']], function () {
    Route::post('logout', [AuthController::class, 'logout']);
});
Route::post('/send-message', [AuthController::class, 'sendMessage']);
Route::get('users', [AuthController::class, 'getUsers']);
Route::get('/users/search', [AuthController::class, 'search']);
Route::get('users/{id_user}', [AuthController::class, 'show']);


//----------------------BROADCAST-------------------------//
Route::get('/send-event', function () {
    event(new MyEvent('Hello, this is a test message!'));
    return 'Event has been sent!';
});
Route::get('/index', [PusherController::class, 'index']);
Route::post('/broadcast', [PusherController::class, 'broadcast']);
Route::post('/receive', [PusherController::class, 'receive']);


//----------------------EMPLOYEE-------------------------//


// Route to create a new employee
Route::post('/employee/new', [EmployeeController::class, 'create']);

// Route to update an employee by ID
Route::put('/employee/update/{id}', [EmployeeController::class, 'update']);

Route::get('/employee/search', [EmployeeController::class, 'search']);

Route::get('/employee/filter', [EmployeeController::class, '']);

// Route to disable an employee by ID (instead of delete)
Route::put('/employee/disable/{id}', [EmployeeController::class, 'disable']);

// Route to fetch employees with optional filters
Route::get('/employee/show', [EmployeeController::class, 'show']);

Route::get('/employee/chief/{id_superior}', [EmployeeController::class, 'getBySuperior']);

Route::get('/employee/count', [EmployeeController::class, 'getEmployeeCounts']);

// Route to get employee statistics
Route::get('/employee/stats', [EmployeeController::class, 'stat']);

Route::get('/employee', [EmployeeController::class, 'index']);



//----------------------UNITY----------------------//

// Route to create a new unity
Route::post('/unity/new', [UnityController::class, 'create']);

// Route to get a list of all unities
Route::get('/unity', [UnityController::class, 'index']);

Route::get('/unity/search', [UnityController::class, 'search']);

// Route to update an existing unity
Route::put('/unity/{id}', [UnityController::class, 'update']);

// Route to delete a unity
Route::delete('/unity/{id}', [UnityController::class, 'delete']);

// Route to get a single unity by ID
Route::get('/unity/{id}', [UnityController::class, 'show']);

Route::get('/unity/position/{id}', [UnityController::class, 'getPosition']);


// Route to get all unity IDs
Route::get('/unity/ids', [UnityController::class, 'ids']);


//---------------------POSITION-----------------------//
Route::prefix('position')->group(function () {
    // Create a new position
    Route::post('/new', [PositionController::class, 'create']);

    // Get all positions
    Route::get('', [PositionController::class, 'index']);

    Route::get('/search', [PositionController::class, 'search']);

    // Get a specific position by ID
    Route::get('/{id}', [PositionController::class, 'show']);

    // Update a position
    Route::put('/update/{id}', [PositionController::class, 'update']);

    // Delete a position
    Route::delete('/delete/{id}', [PositionController::class, 'delete']);
});


//----------------------RESOURCE-----------------------//

Route::prefix('resource')->group(function () {

    // Static routes (placed before parameterized routes)
    Route::get('/mandeha', [ResourceController::class, 'getAvailableResources']);
    Route::get('/count', [ResourceController::class, 'resourceCounts']);
    Route::get('/search', [ResourceController::class, 'search']);
    Route::get('/chief/{chiefId}', [ResourceController::class, 'getResourcesByChief']);

    // Retrieve all resources
    Route::get('/', [ResourceController::class, 'index']);

    // Retrieve a specific resource by ID
    Route::get('/{id}', [ResourceController::class, 'show']);

    // Create a new resource
    Route::post('/new', [ResourceController::class, 'create']);

    // Update a specific resource by ID
    Route::put('/update/{id}', [ResourceController::class, 'update']);

    // Delete a specific resource by ID
    Route::delete('/delete/{id}', [ResourceController::class, 'destroy']);

    // Import multiple resources in bulk
    Route::post('/import', [ResourceController::class, 'import']);
});


//---------------------REQUEST-------------------------//

Route::prefix('request')->group(function () {
    // Create a new request
    Route::post('/new', [RequestController::class, 'create']);

    // Retrieve all requests
    Route::get('/', [RequestController::class, 'index']);

    // Retrieve all filtered requests
    Route::get('/filter', [RequestController::class, 'filterRequests']);

    // Research all requests
    Route::get('/search', [RequestController::class, 'search']);

    // Retrieve a specific request by ID
    Route::get('/{id}', [RequestController::class, 'show']);

    // Update an existing request by ID
    Route::put('/{id}', [RequestController::class, 'update']);

    // Delete a request by ID
    Route::delete('/{id}', [RequestController::class, 'delete']);

    // Retrieve requests by requester ID
    Route::get('/by-requester/{requesterId}', [RequestController::class, 'getByRequester']);

    // Retrieve sent requests by requester ID
    Route::get('/sent/{requesterId}', [RequestController::class, 'getSentRequests']);

    // Retrieve received requests by receiver ID
    Route::get('/received/{receiverId}', [RequestController::class, 'getReceivedRequests']);

    Route::post('/bulk', [RequestController::class, 'handleRequests']);
});

//----------------------VALIDATION---------------------//

Route::prefix('validation')->group(function () {
    // Retrieve all validations
    Route::get('/', [ValidationController::class, 'index']);

    // Retrieve a specific validation by ID
    Route::get('/{id}', [ValidationController::class, 'show']);

    // Create a new validation
    Route::post('/new', [ValidationController::class, 'create']);

    // Update a validation by ID
    Route::put('/{id}', [ValidationController::class, 'update']);

    // Delete a validation by ID
    Route::delete('/{id}', [ValidationController::class, 'delete']);
});


//----------------------NOTIFICATION-------------------//


