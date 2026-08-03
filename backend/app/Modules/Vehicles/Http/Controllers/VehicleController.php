<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Enums\FuelType;
use App\Modules\Vehicles\Enums\VehicleType;
use App\Modules\Vehicles\Http\Requests\IndexVehicleRequest;
use App\Modules\Vehicles\Http\Requests\StoreVehicleRequest;
use App\Modules\Vehicles\Http\Requests\UpdateVehicleRequest;
use App\Modules\Vehicles\Http\Requests\UploadVehiclePhotoRequest;
use App\Modules\Vehicles\Http\Resources\VehicleResource;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    public function __construct(private readonly VehicleService $vehicles) {}

    /**
     * GET /api/vehicles
     */
    public function index(IndexVehicleRequest $request): AnonymousResourceCollection
    {
        return VehicleResource::collection(
            $this->vehicles->paginate($request->filters())
        );
    }

    /**
     * GET /api/vehicles/metadata
     *
     * Catálogos para poblar los formularios y filtros del frontend.
     */
    public function metadata(): JsonResponse
    {
        return response()->json([
            'data' => [
                'types' => VehicleType::options(),
                'fuel_types' => FuelType::options(),
                'brands' => $this->vehicles->brands(),
                'sortable' => IndexVehicleRequest::SORTABLE,
                'year_range' => ['min' => 1900, 'max' => ((int) date('Y')) + 1],
            ],
        ]);
    }

    /**
     * GET /api/vehicles/export/excel
     */
    public function exportExcel(IndexVehicleRequest $request)
    {
        $vehicles = $this->vehicles->getForExport($request->filters());

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Modules\Vehicles\Exports\VehiclesExport($vehicles),
            'Flota.xlsx'
        );
    }

    /**
     * GET /api/vehicles/export/pdf
     */
    public function exportPdf(IndexVehicleRequest $request): Response
    {
        $vehicles = $this->vehicles->getForExport($request->filters());

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.vehicles-report', compact('vehicles'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('Reporte_Vehiculos.pdf');
    }

    /**
     * POST /api/vehicles
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->vehicles->create($request->validated());

        return VehicleResource::make($vehicle)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/vehicles/{vehicle}
     */
    public function show(Vehicle $vehicle): VehicleResource
    {
        return VehicleResource::make($vehicle);
    }

    /**
     * PUT|PATCH /api/vehicles/{vehicle}
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): VehicleResource
    {
        return VehicleResource::make(
            $this->vehicles->update($vehicle, $request->validated())
        );
    }

    /**
     * DELETE /api/vehicles/{vehicle}
     *
     * Borrado lógico: el registro se puede recuperar con /restore.
     */
    public function destroy(Vehicle $vehicle): Response
    {
        $this->vehicles->delete($vehicle);

        return response()->noContent();
    }

    /**
     * POST /api/vehicles/{vehicle}/restore
     */
    public function restore(Vehicle $vehicle): VehicleResource
    {
        return VehicleResource::make(
            $this->vehicles->restore($vehicle)
        );
    }

    /**
     * POST /api/vehicles/{vehicle}/photo
     *
     * Sube o reemplaza la foto del vehículo.
     */
    public function uploadPhoto(UploadVehiclePhotoRequest $request, Vehicle $vehicle): VehicleResource
    {
        return VehicleResource::make(
            $this->vehicles->updatePhoto($vehicle, $request->file('photo'))
        );
    }

    /**
     * DELETE /api/vehicles/{vehicle}/photo
     */
    public function destroyPhoto(Vehicle $vehicle): VehicleResource
    {
        return VehicleResource::make(
            $this->vehicles->deletePhoto($vehicle)
        );
    }
}
