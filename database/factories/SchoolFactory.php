<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\School>
 */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' School',
            'type' => fake()->randomElement(School::getSchoolTypes()),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'region' => fake()->state(),
            'country' => 'Malawi',
            'postal_code' => fake()->optional()->postcode(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->optional()->url(),
            'motto' => fake()->optional()->sentence(4),
            'established_year' => fake()->optional()->year(),
            'registration_number' => fake()->optional()->numerify('SCH-####'),
            'status' => School::STATUS_ACTIVE,
            'enabled_modules' => School::getDefaultModules(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => School::TYPE_PRIMARY,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => School::TYPE_SECONDARY,
        ]);
    }

    public function international(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => School::TYPE_INTERNATIONAL,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => School::STATUS_SUSPENDED,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => School::STATUS_PENDING,
        ]);
    }
}
