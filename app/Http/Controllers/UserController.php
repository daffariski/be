<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Mechanic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\LightControllerHelper;

class UserController extends Controller
{
    use LightControllerHelper;

    // ========================================>
    // ## Display a listing of the resource.
    // ========================================>
    public function index(Request $request)
    {
        // ? Initial params
        $params = $this->getParams($request);

        // ? Begin
        $query = User::query()
            ->where(function ($query) {
                $query->whereHas('admin')
                    ->orWhereHas('mechanic');
            })
            ->with(['mechanic:id,user_id', 'admin:id,user_id'])
            ->search($params["search"] || '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->selectableColumns()
            ->paginate($params["paginate"]);

        // ? Response
        $this->responseData($query->all(), $query->total());
    }

    // =============================================>
    // ## Store a newly created resource.
    // =============================================>
    public function store(Request $request)
    {
        // ? Validate request
        $this->validation($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,mechanic', // Added role validation
        ]);

        // ? Initial
        DB::beginTransaction();
        $user = new User();

        // ? Dump data
        $user->fill($request->only(['name', 'email']));
        $user->password = Hash::make($request->password);

        // ? Executing
        try {
            $user->save();
            if ($request->role === 'admin') {
                Admin::create(['user_id' => $user->id]);
            } elseif ($request->role === 'mechanic') {
                Mechanic::create(['user_id' => $user->id]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Create User');
        }

        // ? final
        DB::commit();
        $this->responseSaved($user->toArray());
    }

    // ============================================>
    // ## Display the specified resource.
    // ============================================>
    public function show(string $id)
    {
        // ? Initial
        $user = User::query()
            ->selectableColumns()
            ->findOrFail($id);

        // ? Response
        $this->responseData($user->toArray());
    }

    // ============================================>
    // ## Update the specified resource.
    // ============================================>
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();
        $user = User::findOrFail($id);

        $this->validation($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
            'role' => 'sometimes|required|in:admin,mechanic,customer',
        ]);

        $user->fill($request->only(['name', 'email', 'role']));

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        try {
            $user->save();
            DB::commit();

            return $this->responseSaved($user->toArray());
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Update User');
        }
    }


    // ===============================================>
    // ## Remove the specified resource.
    // ===============================================>
    public function destroy(string $id)
    {
        // ? Initial
        $user = User::findOrFail($id);

        // ? Executing
        try {
            $user->delete();
        } catch (\Throwable $th) {
            $this->responseError($th, 'Delete User');
        }

        // ? final
        $this->responseData(['message' => 'User deleted successfully']);
    }

    // ===============================================>
    // ## Assign user as Admin
    // ===============================================>
    public function assignAdmin(string $id)
    {
        // ? Initial
        DB::beginTransaction();
        $user = User::findOrFail($id);

        // ? Check if already an admin
        if ($user->admin) {
            return $this->responseData(['message' => 'User is already an Admin'], 200);
        }

        // ? Executing
        try {
            Admin::create(['user_id' => $user->id]);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Assign Admin');
        }

        // ? final
        DB::commit();
        return $this->responseSaved(['message' => 'User assigned as Admin successfully']);
    }

    // ===============================================>
    // ## Assign user as Mechanic
    // ===============================================>
    public function assignMechanic(string $id)
    {
        // ? Initial
        DB::beginTransaction();
        $user = User::findOrFail($id);

        // ? Check if already a mechanic
        if ($user->mechanic) {
            return $this->responseData(['message' => 'User is already a Mechanic'], 200);
        }

        // ? Executing
        try {
            Mechanic::create(['user_id' => $user->id]);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Assign Mechanic');
        }

        // ? final
        DB::commit();
        return $this->responseSaved(['message' => 'User assigned as Mechanic successfully']);
    }
}
