<?php

declare(strict_types=1);

namespace App\Modules\Persons\Models;

use App\Modules\Persons\Database\Factories\PersonFactory;
use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\Gender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property DocumentType $document_type
 * @property string $document_number
 * @property string $first_name
 * @property string $last_name
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property Gender $gender
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property bool $is_active
 * @property string|null $notes
 */
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'email',
        'phone',
        'address',
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
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): PersonFactory
    {
        return PersonFactory::new();
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

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower($term)).'%';

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
