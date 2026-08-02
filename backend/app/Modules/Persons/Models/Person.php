<?php

declare(strict_types=1);

namespace App\Modules\Persons\Models;

use App\Modules\Persons\Database\Factories\PersonFactory;
use App\Modules\Persons\Enums\Area;
use App\Modules\Persons\Enums\ContractType;
use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\EducationLevel;
use App\Modules\Persons\Enums\Gender;
use App\Modules\Persons\Enums\MaritalStatus;
use App\Modules\Persons\Enums\PensionSystem;
use App\Modules\Persons\Enums\Site;
use App\Modules\Persons\Enums\WorkShift;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Modules\Persons\Observers\PersonObserver;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property DocumentType $document_type
 * @property string $document_number
 * @property string $first_name
 * @property string $last_name
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property Gender $gender
 * @property MaritalStatus|null $marital_status
 * @property EducationLevel|null $education_level
 * @property int|null $children_count
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $address
 * @property string|null $ruc
 * @property PensionSystem|null $pension_system
 * @property Site|null $site
 * @property Area|null $area
 * @property string|null $position
 * @property ContractType|null $contract_type
 * @property \Illuminate\Support\Carbon|null $hire_date
 * @property WorkShift|null $work_shift
 * @property bool $is_active
 * @property string|null $notes
 * @property string|null $photo_path
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection $media
 */
#[ObservedBy([PersonObserver::class])]
class Person extends Model implements HasMedia
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    protected $table = 'people';

    protected $fillable = [
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'marital_status',
        'education_level',
        'children_count',
        'email',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'ruc',
        'pension_system',
        'site',
        'area',
        'position',
        'contract_type',
        'hire_date',
        'work_shift',
        'is_active',
        'notes',
    ];

    /**
     * Valores por defecto a nivel de modelo.
     *
     * Duplican los DEFAULT de la migración a propósito: los de la base de datos
     * no se reflejan en la instancia recién creada, y sin esto un `create()` sin
     * `gender` devolvería null donde el recurso espera un enum.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'document_type' => DocumentType::NationalId->value,
        'gender' => Gender::Undisclosed->value,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'education_level' => EducationLevel::class,
            'site' => Site::class,
            'area' => Area::class,
            'contract_type' => ContractType::class,
            'work_shift' => WorkShift::class,
            'pension_system' => PensionSystem::class,
            'birth_date' => 'date',
            'hire_date' => 'date',
            'is_active' => 'boolean',
            'children_count' => 'integer',
        ];
    }

    protected static function newFactory(): PersonFactory
    {
        return PersonFactory::new();
    }

    // ----------------------------------------------------------- Relaciones

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Audit, $this>
     */
    public function audits(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Audit::class, 'auditable');
    }

    // ----------------------------------------------------------- Atributos

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->birth_date?->age);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->getFirstMediaUrl('avatar') ?: null
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
        $this->addMediaCollection('documents');
    }

    // ------------------------------------------------------------- Scopes

    /**
     * Busca en nombre, apellido, documento y correo.
     *
     * @param  Builder<Person>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_strtolower($term)).'%';

        $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('LOWER(first_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", [$like])
                ->orWhereRaw('LOWER(document_number) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$like]);
        });
    }

    /**
     * @param  Builder<Person>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
