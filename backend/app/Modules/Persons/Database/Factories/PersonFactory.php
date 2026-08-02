<?php

declare(strict_types=1);

namespace App\Modules\Persons\Database\Factories;

use App\Modules\Persons\Enums\Area;
use App\Modules\Persons\Enums\ContractType;
use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\EducationLevel;
use App\Modules\Persons\Enums\Gender;
use App\Modules\Persons\Enums\MaritalStatus;
use App\Modules\Persons\Enums\PensionSystem;
use App\Modules\Persons\Enums\Site;
use App\Modules\Persons\Enums\WorkShift;
use App\Modules\Persons\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => fake()->randomElement(DocumentType::cases()),
            'document_number' => (string) fake()->unique()->numerify('########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years'),
            'gender' => fake()->randomElement(Gender::cases()),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('9########'),
            'address' => fake()->address(),
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'education_level' => fake()->randomElement(EducationLevel::cases()),
            'children_count' => fake()->numberBetween(0, 5),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('9########'),
            'ruc' => fake()->numerify('10#########'),
            'pension_system' => fake()->randomElement(PensionSystem::cases()),
            'area' => fake()->randomElement(Area::cases()),
            'position' => fake()->jobTitle(),
            'contract_type' => fake()->randomElement(ContractType::cases()),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'work_shift' => fake()->randomElement(WorkShift::cases()),
            'site' => fake()->randomElement(Site::cases()),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
