<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Events\MyEvent;
use App\Http\Controllers\Api\EmployeeController;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware('web')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/send-event', function () {
    event(new MyEvent('Hello, this is a test message!'));
    return 'Event has been sent!';
});
Route::post('/employees', [EmployeeController::class, 'create']);
