<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Events\MessageSent;
use App\Events\UserActionEvent;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matricule' => 'required|string|unique:employees,matricule',
            'username' => 'required|string|unique:useraccount',
            'password' => 'required|string|min:6',
            'discriminator' => 'required|string|in:admin,unitychief,recruit',
            'id_superior' => 'nullable|exists:useraccount,matricule',
            'remember_me' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Create the user
        $user = User::create([
            'matricule' => $request->matricule,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'discriminator' => $request->discriminator,
            'isactive' => false,
            'id_superior' => $request->id_superior,
            'remember_me' => $request->remember_me ?? false,
        ]);

        event(new UserActionEvent($user, 'create'));

        // $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            // 'token' => $token,
            'discriminator' => $user->discriminator,
            'isactive' => $user->isactive,
            'user' => $user->load('employee.position')
        ], 201);
    }
    


    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->all());

        // Dispatch the event with 'update' action
        event(new UserActionEvent($user, 'update'));

        return response()->json(['message' => 'User updated successfully!', 'user' => $user], 200);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        // Dispatch the event with 'delete' action
        event(new UserActionEvent($user, 'delete'));

        return response()->json(['message' => 'User deleted successfully!'], 200);
    }
    


    public function login(Request $request)
{
    // Validate query (either username or matricule) and password input
    $validator = Validator::make($request->all(), [
        'query' => 'required|string', // Single field for username or matricule
        'password' => 'required|string|min:6',
        'remember_me' => 'boolean', // Optional field
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Debugging log to confirm the received query
    \Log::info('Login attempt:', ['query' => $request->query]);

    // Attempt to find the user by username or matricule
    $queryValue = $request->input('query');
    $user = User::whereRaw('LOWER(username) = ?', [strtolower($queryValue)])
        ->orWhereRaw('LOWER(matricule) = ?', [strtolower($queryValue)])
        ->first();

    if (!$user) {
        return response()->json([
            'error' => ['The username or matricule is not found in our database.'],
            'debug' => ['query' => $queryValue],
        ], 404);
    }

    // Prepare credentials for JWTAuth attempt
    $credentials = [
        $user->username === $queryValue ? 'username' : 'matricule' => $queryValue,
        'password' => $request->password,
    ];

    // Attempt to authenticate with credentials
    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => ['The provided credentials are incorrect.']], 401);
    }

    // Set remember_me to false if not provided
    $rememberMe = $request->input('remember_me', false);

    // Set JWT time-to-live based on remember_me
    config(['jwt.ttl' => $rememberMe ? 20160 : 60]);

    // Retrieve the authenticated user
    $authenticatedUser = JWTAuth::user();

    // Update the user's 'isactive' status to true
    $authenticatedUser->isactive = true;
    $authenticatedUser->save();

    // Return the authentication response
    return response()->json([
        'success' => true,
        'token' => $token,
        'discriminator' => $authenticatedUser->discriminator,
        'isactive' => $authenticatedUser->isactive,
        'user' => $authenticatedUser->load('employee.position'),
    ]);
}

    // public function login(Request $request)
    // {
    //     // Validate the request
    //     $request->validate([
    //         'query' => 'required|string', // Can be either username or matricule
    //         'password' => 'required|string',
    //     ]);

    //     $query = $request->input('query');
    //     $password = $request->input('password');

    //     // Attempt to find the user by matricule or username
    //     $user = User::where('matricule', $query)
    //         ->orWhere('username', $query)
    //         ->first();

    //     if (!$user) {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }

    //     // Check if the password matches
    //     if (!Hash::check($password, $user->password)) {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }

    //     // Generate the token for the user
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'message' => 'Login successful',
    //         'token' => $token,
    //         'user' => $user,
    //     ]);
    // }




    // Logout function
    public function logout(Request $request)
    {
        try {
            // Retrieve the authenticated user
            $user = JWTAuth::user();

            // Update the user's 'isactive' status to false
            $user->isactive = false;
            $user->save();

            // Invalidate the JWT token
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['success' => true, 'message' => 'Successfully logged out', 'isactive'=> $user->isactive]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout, please try again.'], 500);
        }
    }


    public function sendMessage(Request $request)
    {
        $message = $request->input('message');
        
        // Trigger the event
        broadcast(new MessageSent($message));

        return response()->json(['status' => 'Message sent!']);
    }

    // Method to get all users
    public function getUsers()
    {
        $users = User::all(); // Retrieve all users from the database

        return response()->json([
            'success' => true,
            'users' => $users
        ], 200);
    }
}