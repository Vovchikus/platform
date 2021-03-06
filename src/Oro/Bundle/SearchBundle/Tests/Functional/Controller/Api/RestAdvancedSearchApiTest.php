<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @group search
 */
class RestAdvancedSearchApiTest extends WebTestCase
{
    use SearchExtensionTrait;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader(), true);

        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->ensureItemsLoaded($alias, 0);

        $this->loadFixtures([LoadSearchItemData::class], true);
        $this->getSearchIndexer()->reindex(Item::class);
        $this->ensureItemsLoaded($alias, LoadSearchItemData::COUNT);
    }

    /**
     * @param array $request
     * @param array $response
     *
     * @dataProvider advancedSearchDataProvider
     */
    public function testAdvancedSearch(array $request, array $response)
    {
        $this->addOroDefaultPrefixToUrlInParameterArray($response['rest']['data'], 'record_url');
        $queryString = $request['query'];
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_search_advanced'),
            ['query' => $queryString]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true);

        //compare result
        $this->assertEquals($response['records_count'], $result['records_count']);
        $this->assertEquals($response['count'], $result['count']);
        $this->assertSameSize($response['rest']['data'], $result['data']);

        // remove ID references
        foreach (array_keys($result['data']) as $key) {
            unset($result['data'][$key]['record_id']);
        }

        $this->assertSame($response['rest']['data'], $result['data']);
    }

    public function testAdvancedSearchNoResults()
    {
        $queryString = 'from oro_test_item where stringValue = item5';

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_search_advanced'),
            ['query' => $queryString]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true);

        $this->assertEquals(0, $result['records_count']);
        $this->assertEquals(0, $result['count']);
        $this->assertArrayNotHasKey('data', $result);
    }

    /**
     * @return array
     */
    public function advancedSearchDataProvider()
    {
        return $this->getApiRequestsData(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'advanced_requests'
        );
    }
}
