<?php

namespace Tests\App\Controller\EnMarche\MunicipalManagerAttribution;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 */
class ReferentMunicipalManagerAttributionControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    public function testReferentCanReachMunicipalManagerAttributionForm()
    {
        $this->authenticateAsAdherent($this->client, 'referent@en-marche-dev.fr');

        $crawler = $this->client->request(Request::METHOD_GET, '/');
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->click($crawler->selectLink('Espace référent')->link());
        $crawler = $this->client->followRedirect();
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $crawler = $this->client->click($crawler->selectLink('Assesseurs')->link());
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $crawler = $this->client->click($crawler->selectLink('Communes')->link());
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $cities = $crawler->filter('.datagrid__table-manager tbody tr');
        $this->assertCount(3, $cities);

        $this->assertStringContainsString('Lille (59350)', $cities->eq(0)->text());
        $this->assertStringContainsString('Roubaix (59512)', $cities->eq(1)->text());
        $this->assertStringContainsString('Seclin (59560)', $cities->eq(2)->text());
    }

    public function testAdherentCanNotSeeMunicipalManagerAttributionForm()
    {
        $this->authenticateAsAdherent($this->client, 'michel.vasseur@example.ch');

        $this->client->request(Request::METHOD_GET, '/espace-referent/responsables-communaux');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();
    }

    protected function tearDown(): void
    {
        $this->kill();

        parent::tearDown();
    }
}
