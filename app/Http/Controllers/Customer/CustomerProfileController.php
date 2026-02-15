<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Helpers\LightControllerHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerProfileController extends Controller
{
    use LightControllerHelper;

    /**
     * Get the authenticated customer's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load('customer');

        if (!$user->customer) {
            return response()->json([
                'message' => 'Customer profile not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->customer->phone,
                'address' => $user->customer->address,
            ],
        ]);
    }

    /**
     * Update the authenticated customer's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $this->validation($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ]);

        try {
            \DB::beginTransaction();

            // Update user data
            if ($request->has('name')) {
                $user->name = $request->name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // Update customer profile
            if ($user->customer) {
                if ($request->has('phone')) {
                    $user->customer->phone = $request->phone;
                }
                if ($request->has('address')) {
                    $user->customer->address = $request->address;
                }
                $user->customer->save();
            }

            \DB::commit();

            $user->load('customer');

            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->customer->phone ?? null,
                    'address' => $user->customer->address ?? null,
                ],
            ]);
        } catch (\Throwable $th) {
            \DB::rollBack();
            return $this->responseError($th, 'Update Profile');
        }
    }
}
