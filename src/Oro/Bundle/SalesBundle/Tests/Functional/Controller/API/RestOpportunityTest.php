<?php

namespace Oro\Bundle\SalesBundle\Tests\Functional\Controller\API;

use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestOpportunityTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateWsseAuthHeader()
        );

        $this->loadFixtures(['Oro\Bundle\SalesBundle\Tests\Functional\Fixture\LoadSalesBundleFixtures']);
    }

    /**
     * @return array
     */
    public function testPostOpportunity()
    {
        $request = [
            'opportunity' => [
                'name'                => 'opportunity_name_' . mt_rand(1, 500),
                'owner'               => '1',
                'contact'             => $this->getReference('default_contact')->getId(),
                'status'              => 'in_progress',
                'customerAssociation' => '{"value":"Account"}', //create with new Account
            ],
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_opportunity'),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $request['id'] = $result['id'];

        return $request;
    }

    /**
     * @param $request
     *
     * @depends testPostOpportunity
     * @return  mixed
     */
    public function testGetOpportunity($request)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_opportunity', ['id' => $request['id']])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($request['id'], $result['id']);
        $this->assertEquals($request['opportunity']['name'], $result['name']);
        // Because api return name of status, that can be different, assert id
        $this->assertEquals('in_progress', $this->getStatusByLabel($result['status'])->getId());
        // Incomplete CRM-816
        //$this->assertEquals($request['opportunity']['owner'], $result['owner']['id']);
        return $request;
    }

    /**
     * @param $request
     *
     * @depends testGetOpportunity
     * @return  mixed
     */
    public function testPutOpportunity($request)
    {
        $request['opportunity']['name'] .= '_updated';

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_opportunity', ['id' => $request['id']]),
            $request
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_opportunity', ['id' => $request['id']])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($request['id'], $result['id']);
        $this->assertEquals($request['opportunity']['name'], $result['name']);
        // Because api return name of status, that can be different, assert id
        $this->assertEquals('in_progress', $this->getStatusByLabel($result['status'])->getId());

        return $request;
    }

    /**
     * @depends testPutOpportunity
     */
    public function testGetOpportunities($request)
    {
        $baseUrl = $this->getUrl('oro_api_get_opportunities');
        $this->client->request('GET', $baseUrl);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        $result = end($result);
        $this->assertEquals($request['id'], $result['id']);
        $this->assertEquals($request['opportunity']['name'], $result['name']);
        // Because api return name of status, that can be different, assert id
        $this->assertEquals('in_progress', $this->getStatusByLabel($result['status'])->getId());

        $this->client->request('GET', $baseUrl . '?contactId=' . $request['opportunity']['contact']);
        $this->assertCount(1, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->request('GET', $baseUrl . '?contactId<>' . $request['opportunity']['contact']);
        $this->assertEmpty($this->getJsonResponseContent($this->client->getResponse(), 200));
    }

    /**
     * @depends testPutOpportunity
     */
    public function testDeleteOpportunity($request)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_opportunity', ['id' => $request['id']])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_opportunity', ['id' => $request['id']])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * @param $result
     * @return EnumValue
     */
    private function getStatusByLabel($statusLabel)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(ExtendHelper::buildEnumValueClassName('opportunity_status'))
            ->findOneByName($statusLabel);
    }
}
