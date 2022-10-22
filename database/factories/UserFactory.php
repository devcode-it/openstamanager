<?php /** @noinspection PhpUnused */

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Illuminate\Support\Carbon;

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
     * @noinspection PhpUndefinedMethodInspection
     * @return array{name: mixed, email: mixed, email_verified_at: \Illuminate\Support\Carbon, password: string, remember_token: string}
     */
    #[ArrayShape(['name' => 'string', 'email' => 'mixed', 'email_verified_at' => Carbon::class, 'password' => 'string', 'remember_token' => 'string'])]
     public function definition(): array
     {
         return [
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->safeEmail(),
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ];
     }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): Factory
    {
        return $this->state(static fn() => [
            'email_verified_at' => null,
        ]);
    }
}
