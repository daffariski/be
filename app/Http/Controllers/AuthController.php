<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Helpers\LightControllerHelper;

class AuthController extends Controller
{
    use LightControllerHelper;

    public function login(Request $request)
    {
        $this->validation($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function register(Request $request)
    {
        $this->validation($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            \DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'customer',
            ]);

            // Create customer profile
            \App\Models\Customer::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            \DB::commit();

            return response()->json([
                'message'      => 'Registration successful',
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user->load('customer'),
            ], 201);
        } catch (\Throwable $th) {
            \DB::rollBack();
            return $this->responseError($th, 'Register User');
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
