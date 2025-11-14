<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\LightControllerHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    use LightControllerHelper;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // ? Initial params
        $params = $this->getParams($request);

        // ? Begin
        $query = User::query()
            ->where('role', 'customer')
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->selectableColumns()
            ->paginate($params["paginate"]);

        // ? Response
        $this->responseData($query->all(), $query->total());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ? Validate request
        $this->validation($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // ? Initial
        DB::beginTransaction();
        $user = new User();

        // ? Dump data
        $user->fill($request->all());
        $user->password = Hash::make($request->password);
        $user->role = 'customer';

        // ? Executing
        try {
            $user->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Create Customer User');
        }

        // ? final
        DB::commit();
        $this->responseSaved($user->toArray());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // ? Initial
        $user = User::query()
            ->where('role', 'customer')
            ->selectableColumns()
            ->findOrFail($id);

        // ? Response
        $this->responseData($user->toArray());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // ? Initial
        DB::beginTransaction();
        $user = User::query()
            ->where('role', 'customer')
            ->findOrFail($id);

        // ? Validate request
        $this->validation($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        // ? Dump data
        $user->fill($request->except(['password']));
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        // ? Executing
        try {
            $user->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Update Customer User');
        }

        // ? final
        DB::commit();
        $this->responseSaved($user->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // ? Initial
        $user = User::query()
            ->where('role', 'customer')
            ->findOrFail($id);

        // ? Executing
        try {
            $user->delete();
        } catch (\Throwable $th) {
            $this->responseError($th, 'Delete Customer User');
        }

        // ? final
        $this->responseData(['message' => 'Customer user deleted successfully']);
    }
}
