<?php

namespace Database\Factories\User;

use App\Models\User\Address;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'username' => $this->faker->name . $this->faker->word,
            'password' => Hash::make('123456'),
            'gender' => $this->faker->randomKey([0, 1, 2]),
            'mobile' => $this->faker->phoneNumber,
            'avatar' => $this->faker->imageUrl(),
        ];
    }

    /**
     * 用户默认地址场景
     * @return UserFactory
     */
    public function addressDefault()
    {
        return $this->state(function () {
            return [];
        })->afterCreating(function (User $user) {
            Address::factory()->create([
                'user_id' => $user->id,
                'is_default' => 1
            ]);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
