<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assignments\Http\Requests\AssignVehicleRequest;
use App\Modules\Assignments\Http\Resources\VehicleAssignmentResource;
use App\Modules\Assignments\Services\AssignmentService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    /**
     * GET /api/assignments/people?search=
     *
     * Búsqueda liviana de personas activas para el selector del frontend.
     */
    public function people(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->assignments->searchPeople($request->string('search')->toString() ?: null),
        ]);
    }

    /**
     * GET /api/assignments/{vehicle}
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        $assignment = $this->assignments->current($vehicle);

        return response()->json([
            'data' => $assignment ? VehicleAssignmentResource::make($assignment) : null,
        ]);
    }

    /**
     * PUT|PATCH /api/assignments/{vehicle}
     *
     * Asigna o reasigna el vehículo (reemplaza la asignación anterior).
     */
    public function store(AssignVehicleRequest $request, Vehicle $vehicle): VehicleAssignmentResource
    {
        $assignment = $this->assignments->assign(
            $vehicle,
            (int) $request->validated('person_id'),
            $request->validated('notes'),
        );

        return VehicleAssignmentResource::make($assignment);
    }

    /**
     * DELETE /api/assignments/{vehicle}
     */
    public function destroy(Vehicle $vehicle): Response
    {
        $this->assignments->unassign($vehicle);

        return response()->noContent();
    }
}
