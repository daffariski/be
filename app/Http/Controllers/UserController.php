<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Mechanic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\LightControllerHelper;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

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
            // ->where(function ($query) {
            //     $query->whereHas('admin')
            //         ->orWhereHas('mechanic');
            // })
            ->with(['mechanic:id,user_id', 'admin:id,user_id', 'customer'])
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->selectableColumns()
            ->paginate($params["paginate"]);

        $data = $query->all();
        // ? Response
        // $this->responseData($query->all());
        return response()->json([
            'message'   => (count($data) ? 'Success' : 'Empty data'),
            'data'      => $data ?? [],
            'total_row' => null,
            'columns'   => null,
        ], count($data) ? 200 : 206);
    }

    // =============================================>
    // ## Store a newly created resource.
    // =============================================>
    public function store(Request $request)
    {
        // ? Validate request
        $this->validation($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone'    => 'sometimes|required_if:role,customer|numeric',
            'address'  => 'sometimes|required_if:role,customer|string|max:500',
            'role'     => 'required|in:admin,mechanic,customer',                   // Added role validation
        ]);

        // ? Initial
        DB::beginTransaction();
        $user = new User();

        // ? Dump data
        // $user->fill($request->only(['name', 'email']));
        //if phone and address exist, except it
        // $exceptedInputs = ['password', 'phone', 'address'];
        $user->fill($request->except(['password', 'phone', 'address']));
        $user->password = Hash::make($request->password);

        // ? Executing
        try {
            $user->save();
            switch ($request->role) {
                case 'customer':
                    $user->customer()->create([
                        'phone'   => $request->phone,
                        'address' => $request->address,
                    ]);
                    break;
                case 'mechanic':
                    Mechanic::create(['user_id' => $user->id]);
                    break;
                case 'admin':
                    Admin::create(['user_id' => $user->id]);
                    break;
                default:
                    throw new NotFoundResourceException('Invalid role specified');
                    break;
            }
            // if ($request->role === 'admin') {
            //     Admin::create(['user_id' => $user->id]);
            // } elseif ($request->role === 'mechanic') {
            //     Mechanic::create(['user_id' => $user->id]);
            // }
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
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
            'phone'    => 'sometimes|required_if:role,customer|numeric',
            'address'  => 'sometimes|required_if:role,customer|string|max:500',
            'role'     => 'sometimes|required|in:admin,mechanic,customer',
        ]);

        $user->fill($request->only(['name', 'email', 'role']));

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        try {
            $user->save();

            if ($user->role === 'customer') {
                // Update or create customer details
                $user->customer()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone'   => $request->phone,
                        'address' => $request->address,
                    ]
                );
            } else {
                // If role changed from customer to another, delete customer details
                if ($user->customer) {
                    $user->customer->delete();
                }
            }
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
