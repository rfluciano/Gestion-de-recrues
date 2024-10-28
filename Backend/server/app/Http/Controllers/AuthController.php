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
            'matricule' => 'required|string|exists:employees,matricule|unique:useraccount,matricule',
            'email' => 'required|email|unique:useraccount',
            'password' => 'required|string|min:6',
            'discriminator' => 'required|string|in:admin,unitychief,recruit',
            'id_superior' => 'nullable|exists:useraccount,id_user',
            'remember_me' => 'boolean',
        ]);
        

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Create the user
        $user = User::create([
            'matricule' => $request->matricule,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'discriminator' => $request->discriminator,
            'isactive' => true,
            'id_superior' => $request->id_superior,
            'remember_me' => $request->remember_me ?? false,
        ]);

        event(new UserActionEvent($user, 'create'));

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'token' => $token,
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
        // Validate email and password input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'remember_me' => 'boolean' // Optional remember_me field
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Check if the user exists by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // If the user is not found, return a descriptive error
            return response()->json(['email' => ['The email address is not found in our database.']], 404);
        }

        // Credentials for JWTAuth attempt
        $credentials = $request->only('email', 'password');

        // Set remember_me to false if not provided
        $rememberMe = $request->input('remember_me', false);

        // Attempt to authenticate with the credentials
        if (!$token = JWTAuth::attempt($credentials)) {
            // If authentication fails, return an error about invalid credentials
            return response()->json(['password' => ['The provided credentials are incorrect.']], 401);
        }

        // Set the TTL (time to live) based on remember_me
        config(['jwt.ttl' => $rememberMe ? 20160 : 60]); // 20160 minutes = 14 days for "remember me", 60 minutes otherwise

        // Retrieve the authenticated user
        $user = JWTAuth::user();

        // Update the user's 'isactive' status to true
        $user->isactive = true;
        $user->save();

        return response()->json([
            'success' => true,
            'token' => $token,
            'discriminator' => $user->discriminator,
            'isactive' => $user->isactive,
            'user' => $user->load('employee.position')        ]);
    }





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