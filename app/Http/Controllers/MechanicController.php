<?php

namespace App\Http\Controllers;

use App\Helpers\LightControllerHelper;
use App\Models\Mechanic;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class MechanicController extends Controller
{
    use LightControllerHelper;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Mechanic $mechanic)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mechanic $mechanic)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mechanic $mechanic)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mechanic $mechanic)
    {
        //
    }

    /**
     * Get a list of mechanics who are not on duty, formatted for select options.
     */
    public function getMechanicOptions()
    {
        $mechanics = Mechanic::whereNull('on_duty_at')
            ->with('user')
            ->get()
            ->map(function ($mechanic) {
                return [
                    'label' => $mechanic->user->name,
                    'value' => $mechanic->id,
                ];
            });

        return Response::json($mechanics);
    }

  public function assignments(Request $request)
{
    try {
        $mechanic = $request->user()->mechanic;

        if (!$mechanic) {
            return response()->json(['message' => 'Mechanic profile not found.'], 404);
        }

        $params = $this->getParams($request);

        $services = Service::from('services as s')
            ->leftJoin('queues as q', 's.queue_id', '=', 'q.id')
            ->where('s.mechanic_id', $mechanic->id)
            ->select('s.*', 'q.queue_number')
            ->when($params['search'], function ($qBuilder, $search) {
                $qBuilder->where('s.description', 'like', "%{$search}%");
            })
            ->orderByRaw('COALESCE(q.queue_number, 99999) ASC')
            ->paginate($params['paginate']);

        // ✅ Safely load relationships on the underlying collection
        $servicesCollection = $services->getCollection();
        $servicesCollection->load(['queue', 'vehicle', 'customer.user']);
        $services->setCollection($servicesCollection);

        return $this->responseData($services->items(), $services->total());

    } catch (\Throwable $th) {
        // ✅ Traditional error response for debugging
        return response()->json([
            'message' => 'Error: Server Side Having Problem!',
            'error' => $th->getMessage(),
            'trace' => $th->getTraceAsString(),
            'section' => 'Mechanic Assignments',
        ], 500);
    }
}



    /**
     * GET /api/mechanic/queues
     * Show mechanic’s active service queue
     */
    public function queues(Request $request)
    {
        try {
            $mechanic = $request->user()->mechanic;

            if (!$mechanic) {
                return response()->json(['message' => 'Mechanic profile not found.'], 404);
            }

            $params = $this->getParams($request);

            $queues = Service::with(['customer.user', 'vehicle'])
                ->where('mechanic_id', $mechanic->id)
                ->whereIn('status', ['waiting', 'process'])
                ->orderBy($params['sortBy'], $params['sortDirection'])
                ->paginate($params['paginate']);

            $this->responseData($queues->items(), $queues->total());
        } catch (\Throwable $th) {
            $this->responseError($th->getMessage(), 'Mechanic Queues');
        }
    }

    /**
     * PATCH /api/mechanic/queues/{id}
     * Update queue (start, finish, cancel)
     */
    public function updateQueue(Request $request, string $id)
    {
        $this->validation($request->all(), [
            'status' => 'required|in:process,done,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $mechanic = $request->user()->mechanic;

            if (!$mechanic) {
                response()->json(['message' => 'Mechanic profile not found.'], 404)->throwResponse();
            }

            $service = Service::where('id', $id)
                ->where('mechanic_id', $mechanic->id)
                ->firstOrFail();

            $allowedTransitions = [
                'waiting' => ['process', 'cancelled'],
                'process' => ['done', 'cancelled'],
            ];

            $currentStatus = $service->status;
            $newStatus = $request->status;

            if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
                response()->json([
                    'message' => "Invalid status transition from {$currentStatus} to {$newStatus}"
                ], 422)->throwResponse();
            }

            $service->status = $newStatus;
            $service->save();

            // ServiceStatusLog::create([
            //     'service_id' => $service->id,
            //     'mechanic_id' => $mechanic->id,
            //     'status' => $newStatus,
            //     'description' => "Mechanic updated status to {$newStatus}",
            // ]);

            DB::commit();
            $this->responseSaved($service->load(['customer.user', 'vehicle'])->toArray(), "Status updated to {$newStatus}");
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th->getMessage(), 'Mechanic Update Queue');
        }
    }
}
