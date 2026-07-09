<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OpenSearch\ScoutDriverPlus\Tests\App\Store;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'lat' => $this->faker->randomNumber(),
            'lon' => $this->faker->randomNumber(),
        ];
    }
}
