<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Unit\Builders;

use OpenSearch\ScoutDriverPlus\Builders\MatchPhraseQueryBuilder;
use OpenSearch\ScoutDriverPlus\Exceptions\QueryBuilderValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\AbstractParameterizedQueryBuilder::class)]
#[CoversClass(MatchPhraseQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\ParameterCollection::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Transformers\GroupedArrayTransformer::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Validators\AllOfValidator::class)]
final class MatchPhraseQueryBuilderTest extends TestCase
{
    private MatchPhraseQueryBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new MatchPhraseQueryBuilder();
    }

    public function test_exception_is_thrown_when_field_is_not_specified(): void
    {
        $this->expectException(QueryBuilderValidationException::class);

        $this->builder
            ->query('this is a test')
            ->buildQuery();
    }

    public function test_exception_is_thrown_when_text_is_not_specified(): void
    {
        $this->expectException(QueryBuilderValidationException::class);

        $this->builder
            ->field('message')
            ->buildQuery();
    }

    public function test_query_with_field_and_text_can_be_built(): void
    {
        $expected = [
            'match_phrase' => [
                'message' => [
                    'query' => 'this is a test',
                ],
            ],
        ];

        $actual = $this->builder
            ->field('message')
            ->query('this is a test')
            ->buildQuery();

        $this->assertSame($expected, $actual);
    }

    public function test_query_with_field_and_text_and_slop_can_be_built(): void
    {
        $expected = [
            'match_phrase' => [
                'message' => [
                    'query' => 'this is a test',
                    'slop' => 0,
                ],
            ],
        ];

        $actual = $this->builder
            ->field('message')
            ->query('this is a test')
            ->slop(0)
            ->buildQuery();

        $this->assertSame($expected, $actual);
    }

    public function test_query_with_field_and_text_and_analyzer_can_be_built(): void
    {
        $expected = [
            'match_phrase' => [
                'message' => [
                    'query' => 'this is a test',
                    'analyzer' => 'english',
                ],
            ],
        ];

        $actual = $this->builder
            ->field('message')
            ->query('this is a test')
            ->analyzer('english')
            ->buildQuery();

        $this->assertSame($expected, $actual);
    }

    public function test_query_with_field_and_text_and_zero_terms_query_can_be_built(): void
    {
        $expected = [
            'match_phrase' => [
                'message' => [
                    'query' => 'this is a test',
                    'zero_terms_query' => 'none',
                ],
            ],
        ];

        $actual = $this->builder
            ->field('message')
            ->query('this is a test')
            ->zeroTermsQuery('none')
            ->buildQuery();

        $this->assertSame($expected, $actual);
    }

    public function test_query_with_field_and_text_and_boost_can_be_built(): void
    {
        $expected = [
            'match_phrase' => [
                'message' => [
                    'query' => 'this is a test',
                    'boost' => 2,
                ],
            ],
        ];

        $actual = $this->builder
            ->field('message')
            ->query('this is a test')
            ->boost(2)
            ->buildQuery();

        $this->assertEquals($expected, $actual);
    }
}
