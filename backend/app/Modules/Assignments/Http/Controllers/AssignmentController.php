<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assignments\Http\Requests\AssignVehicleRequest;
use App\Modules\Assignments\Http\Resources\VehicleAssignmentResource;
use App\Modules\Assignments\Models\Site;
use App\Modules\Assignments\Services\AssignmentService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    /**
     * GET /api/assignments?site_id=
     *
     * Asignaciones actuales de todos los vehículos (una por vehículo, la
     * vigente), opcionalmente filtradas por sede.
     */
    public function index(Request $request): JsonResponse
    {
        $siteId = $request->integer('site_id') ?: null;

        return response()->json([
            'data' => VehicleAssignmentResource::collection($this->assignments->currentAll($siteId)),
        ]);
    }

    /**
     * GET /api/assignments/sites
     */
    public function sites(): JsonResponse
    {
        return response()->json([
            'data' => $this->assignments->sites()->map(fn (Site $site): array => [
                'id' => $site->id,
                'code' => $site->code,
                'name' => $site->name,
            ]),
        ]);
    }

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
     * GET /api/assignments/{vehicle}/history
     */
    public function history(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'data' => VehicleAssignmentResource::collection($this->assignments->history($vehicle)),
        ]);
    }

    /**
     * PUT|PATCH /api/assignments/{vehicle}
     *
     * Traslada/(re)asigna el vehículo: cierra la asignación activa (si la
     * hay) y crea una nueva.
     *
     * `AssignmentService::assign()` siempre inserta una fila nueva (así se
     * construye el historial), así que el `wasRecentlyCreated` del modelo
     * devuelto es siempre `true` y no sirve para distinguir "primera
     * asignación" (201) de "traslado/reasignación" (200) como haría
     * Laravel por defecto (`ResourceResponse::calculateStatus()`). Por eso
     * el status code se decide explícitamente aquí, mirando si el vehículo
     * ya tenía una asignación vigente antes de esta llamada.
     */
    public function store(AssignVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $personId = $request->validated('person_id');
        $hadActiveAssignment = $this->assignments->current($vehicle) !== null;

        $assignment = $this->assignments->assign(
            $vehicle,
            (int) $request->validated('site_id'),
            $personId !== null ? (int) $personId : null,
            $request->validated('expected_return_at'),
            $request->validated('notes'),
        );

        return VehicleAssignmentResource::make($assignment)
            ->response()
            ->setStatusCode($hadActiveAssignment ? Response::HTTP_OK : Response::HTTP_CREATED);
    }

    /**
     * DELETE /api/assignments/{vehicle}
     *
     * Cierra la asignación activa (no borra el historial).
     */
    public function destroy(Vehicle $vehicle): Response
    {
        $this->assignments->unassign($vehicle);

        return response()->noContent();
    }
}
