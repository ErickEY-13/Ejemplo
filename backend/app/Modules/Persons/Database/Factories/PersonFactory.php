<?php

declare(strict_types=1);

namespace App\Modules\Persons\Database\Factories;

use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\Gender;
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
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
