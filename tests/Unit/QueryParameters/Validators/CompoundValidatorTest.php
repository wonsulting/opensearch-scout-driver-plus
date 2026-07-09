<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Unit\QueryParameters\Validators;

use OpenSearch\ScoutDriverPlus\Exceptions\QueryBuilderValidationException;
use OpenSearch\ScoutDriverPlus\QueryParameters\ParameterCollection;
use OpenSearch\ScoutDriverPlus\QueryParameters\Validators\AllOfValidator;
use OpenSearch\ScoutDriverPlus\QueryParameters\Validators\CompoundValidator;
use OpenSearch\ScoutDriverPlus\QueryParameters\Validators\OneOfValidator;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(CompoundValidator::class)]
#[UsesClass(ParameterCollection::class)]
#[UsesClass(AllOfValidator::class)]
#[UsesClass(OneOfValidator::class)]
final class CompoundValidatorTest extends TestCase
{
    public static function invalidParametersDataProvider(): array
    {
        return [
            [['field' => 'age']],
            [['gt' => 10]],
            [['lt' => 20]],
        ];
    }

    public static function validParametersDataProvider(): array
    {
        return [
            [['field' => 'age', 'gt' => 10]],
            [['field' => 'age', 'gte' => 10]],
            [['field' => 'age', 'lt' => 20]],
            [['field' => 'age', 'lte' => 20]],
            [['field' => 'age', 'gt' => 10, 'lt' => 20]],
            [['field' => 'age', 'gte' => 10, 'lte' => 20]],
        ];
    }

    #[DataProvider('invalidParametersDataProvider')]
    public function test_exception_is_thrown_when_one_of_validations_fails(array $parameters): void
    {
        $this->expectException(QueryBuilderValidationException::class);

        $parameters = new ParameterCollection($parameters);

        $validator = new CompoundValidator(
            new AllOfValidator(['field']),
            new OneOfValidator(['gt', 'lt'])
        );

        $validator->validate($parameters);
    }

    #[DataProvider('validParametersDataProvider')]
    public function test_exception_is_not_thrown_when_all_validations_succeed(array $parameters): void
    {
        $parameters = new ParameterCollection($parameters);

        $validator = new CompoundValidator(
            new AllOfValidator(['field']),
            new OneOfValidator(['gt', 'lt', 'gte', 'lte'])
        );

        $validator->validate($parameters);

        $this->assertTrue(true);
    }
}
