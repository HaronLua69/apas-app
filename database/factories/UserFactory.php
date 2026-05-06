<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'id_number' => sprintf('%04d-%04d', fake()->numberBetween(2018, 2026), fake()->unique()->numberBetween(1, 9999)),
            'username' => fake()->unique()->userName(),
            'name' => $firstName.' '.$lastName,
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'name_suffix' => null,
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::Student,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Administrator,
        ]);
    }

    public function adviser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Adviser,
        ]);
    }

    public function secondaryRole(UserRole|string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'secondary_role' => $role instanceof UserRole ? $role : UserRole::from($role),
        ]);
    }
}
