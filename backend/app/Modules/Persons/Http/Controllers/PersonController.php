<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Persons\Enums\Area;
use App\Modules\Persons\Enums\ContractType;
use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\EducationLevel;
use App\Modules\Persons\Enums\Gender;
use App\Modules\Persons\Enums\MaritalStatus;
use App\Modules\Persons\Enums\PensionSystem;
use App\Modules\Persons\Enums\PersonDocumentType;
use App\Modules\Persons\Enums\Site;
use App\Modules\Persons\Enums\WorkShift;
use App\Modules\Persons\Http\Requests\IndexPersonRequest;
use App\Modules\Persons\Http\Requests\StorePersonRequest;
use App\Modules\Persons\Http\Requests\UpdatePersonRequest;
use App\Modules\Persons\Http\Requests\UploadPersonDocumentRequest;
use App\Modules\Persons\Http\Requests\UploadPersonPhotoRequest;
use App\Modules\Persons\Http\Resources\PersonDocumentResource;
use App\Modules\Persons\Http\Resources\PersonResource;
use App\Modules\Persons\Models\Person;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Modules\Persons\Services\PersonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PersonController extends Controller
{
    public function __construct(private readonly PersonService $persons) {}

    /**
     * GET /api/persons
     */
    public function index(IndexPersonRequest $request): AnonymousResourceCollection
    {
        return PersonResource::collection(
            $this->persons->paginate($request->filters())
        );
    }

    /**
     * GET /api/persons/export/excel
     */
    public function exportExcel(IndexPersonRequest $request)
    {
        $persons = $this->persons->getForExport($request->filters());

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Modules\Persons\Exports\PersonsExport($persons),
            'Empleados.xlsx'
        );
    }

    /**
     * GET /api/persons/export/pdf
     */
    public function exportPdf(IndexPersonRequest $request): Response
    {
        $persons = $this->persons->getForExport($request->filters());

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.persons-report', compact('persons'))
            ->setPaper('a4', 'landscape');
        
        return $pdf->download("Reporte_Empleados.pdf");
    }

    /**
     * POST /api/persons/import/csv
     */
    public function importCsv(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:10240']);
        
        $path = $request->file('file')->store('imports', 'local');
        $jobId = (string) \Illuminate\Support\Str::uuid();
        
        \Illuminate\Support\Facades\Cache::put("import_progress_{$jobId}", [
            'status' => 'pending',
            'progress' => 0,
            'total' => 0,
        ], 3600);

        \App\Modules\Persons\Jobs\ImportPersonsCsvJob::dispatch($path, $jobId);

        return response()->json(['jobId' => $jobId]);
    }

    /**
     * GET /api/persons/import/csv/{jobId}/status
     */
    public function importStatus(string $jobId): JsonResponse
    {
        $status = \Illuminate\Support\Facades\Cache::get("import_progress_{$jobId}");
        
        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($status);
    }

    /**
     * GET /api/persons/metadata
     *
     * Catálogos para poblar los formularios del frontend.
     */
    public function metadata(): JsonResponse
    {
        return response()->json([
            'data' => [
                'document_types' => DocumentType::options(),
                'genders' => Gender::options(),
                'marital_statuses' => MaritalStatus::options(),
                'education_levels' => EducationLevel::options(),
                'areas' => Area::options(),
                'contract_types' => ContractType::options(),
                'work_shifts' => WorkShift::options(),
                'pension_systems' => PensionSystem::options(),
                'sites' => Site::options(),
                'document_types_attach' => PersonDocumentType::options(),
                'sortable' => IndexPersonRequest::SORTABLE,
            ],
        ]);
    }

    /**
     * POST /api/persons
     */
    public function store(StorePersonRequest $request): JsonResponse
    {
        $person = $this->persons->create($request->validated());

        return PersonResource::make($person)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/persons/{person}
     */
    public function show(Person $person): PersonResource
    {
        return PersonResource::make($person->load('media'));
    }

    /**
     * PUT|PATCH /api/persons/{person}
     */
    public function update(UpdatePersonRequest $request, Person $person): PersonResource
    {
        return PersonResource::make(
            $this->persons->update($person, $request->validated())
        );
    }

    /**
     * DELETE /api/persons/{person}
     *
     * Borrado lógico: el registro se puede recuperar con /restore.
     */
    public function destroy(Person $person): Response
    {
        $this->persons->delete($person);

        return response()->noContent();
    }

    /**
     * POST /api/persons/{person}/restore
     */
    public function restore(Person $person): PersonResource
    {
        return PersonResource::make(
            $this->persons->restore($person)
        );
    }

    // ------------------------------------------------------------- Auditoría

    /**
     * GET /api/persons/{person}/audits
     */
    public function audits(Person $person): AnonymousResourceCollection
    {
        return \App\Modules\Persons\Http\Resources\AuditResource::collection(
            $person->audits()->orderBy('created_at', 'desc')->get()
        );
    }

    // ------------------------------------------------------------------- PDF

    /**
     * GET /api/persons/{person}/pdf
     */
    public function pdf(Person $person): Response
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.person-profile', compact('person'));
        
        return $pdf->download("Ficha_{$person->document_number}.pdf");
    }

    // --------------------------------------------------------------- Foto

    /**
     * POST /api/persons/{person}/photo
     *
     * Sube o reemplaza la foto de la persona.
     */
    public function uploadPhoto(UploadPersonPhotoRequest $request, Person $person): PersonResource
    {
        return PersonResource::make(
            $this->persons->updatePhoto($person, $request->file('photo'))
        );
    }

    /**
     * DELETE /api/persons/{person}/photo
     */
    public function destroyPhoto(Person $person): PersonResource
    {
        return PersonResource::make(
            $this->persons->deletePhoto($person)
        );
    }

    // --------------------------------------------------------- Documentos

    /**
     * POST /api/persons/{person}/documents
     */
    public function storeDocument(UploadPersonDocumentRequest $request, Person $person): JsonResponse
    {
        $document = $this->persons->addDocument(
            $person,
            $request->file('file'),
            PersonDocumentType::from($request->validated('type'))
        );

        return PersonDocumentResource::make($document)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * DELETE /api/persons/{person}/documents/{document}
     */
    public function destroyDocument(Person $person, Media $document): Response
    {
        abort_unless($document->model_id === $person->id && $document->model_type === Person::class, 404);

        $this->persons->removeDocument($document);

        return response()->noContent();
    }
}
