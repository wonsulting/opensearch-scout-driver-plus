<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\App\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use OpenSearch\ScoutDriverPlus\Tests\App\Author;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->realText(),
            'price' => $this->faker->randomNumber(3),
            'published' => Carbon::createFromFormat('Y-m-d', $this->faker->date('Y-m-d')),
            'tags' => $this->faker->words(random_int(1, 5)),
        ];
    }

    public function belongsToAuthor(): static
    {
        return $this->state([
            'author_id' => Author::factory(),
        ]);
    }
}
