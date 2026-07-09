<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OpenSearch\ScoutDriverPlus\Tests\App\Author;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'phone_number' => $this->faker->unique()->e164PhoneNumber,
            'email' => $this->faker->unique()->email,
        ];
    }

    public function withBooks(): static
    {
        return $this->afterCreating(static function (Author $author): void {
            $author->books()->saveMany(Book::factory()->count(random_int(1, 10))->make());
        });
    }
}
