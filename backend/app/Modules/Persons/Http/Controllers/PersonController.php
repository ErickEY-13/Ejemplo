<?php

declare(strict_types=1);

namespace App\Modules\Persons\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\Gender;
use App\Modules\Persons\Http\Requests\IndexPersonRequest;
use App\Modules\Persons\Http\Requests\StorePersonRequest;
use App\Modules\Persons\Http\Requests\UpdatePersonRequest;
use App\Modules\Persons\Http\Resources\PersonResource;
use App\Modules\Persons\Models\Person;
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
        return PersonResource::make($person);
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
}
