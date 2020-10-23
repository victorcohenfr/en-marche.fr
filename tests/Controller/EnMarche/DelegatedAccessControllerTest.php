<?php

namespace Tests\App\Controller\EnMarche;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tests\App\Controller\ControllerTestTrait;

class DelegatedAccessControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    public function testSetDelegatedAccessUuidInSessionWhenGoingToDelegatedSpace()
    {
        $this->authenticateAsAdherent($this->client, 'referent@en-marche-dev.fr');

        $crawler = $this->client->request('GET', '/');
        $this->assertFalse($this->client->getRequest()->getSession()->has('delegated_access_uuid'));

        $this->client->click($crawler->selectLink('Espace député partagé (FDE-06)')->link());
        $this->assertTrue($this->client->getRequest()->getSession()->has('delegated_access_uuid'));
        $this->assertEquals('2e80d106-4bcb-4b28-97c9-3856fc235b27', $this->client->getRequest()->getSession()->get('delegated_access_uuid'));

        $this->client->click($crawler->selectLink('Espace référent')->link());
        $this->assertFalse($this->client->getRequest()->getSession()->has('delegated_access_uuid'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();

        $this->client->followRedirects();
    }

    protected function tearDown(): void
    {
        $this->kill();

        parent::tearDown();
    }
}
