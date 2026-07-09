<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Queries;

use OpenSearch\ScoutDriverPlus\Support\Query;
use OpenSearch\ScoutDriverPlus\Tests\App\Store;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\AbstractParameterizedQueryBuilder::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\GeoDistanceQueryBuilder::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\LazyModelFactory::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\ModelFactory::class)]
#[CoversClass(Query::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\DatabaseQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\SearchParametersBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\Hit::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\SearchResult::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\DocumentFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\ParameterFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\ParameterCollection::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Transformers\CallbackArrayTransformer::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Validators\AllOfValidator::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class GeoDistanceQueryTest extends TestCase
{
    public function test_models_can_be_found_using_field_and_distance_and_lat_lon(): void
    {
        // additional mixin
        Store::factory()->count(rand(2, 10))->create([
            'lat' => 20,
            'lon' => 20,
        ]);

        $target = Store::factory()->create([
            'lat' => 10,
            'lon' => 10,
        ]);

        $query = Query::geoDistance()
            ->field('location')
            ->distance('500km')
            ->lat(8)
            ->lon(8);

        $found = Store::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);
    }
}
