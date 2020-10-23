<?php

namespace Tests\App\Controller\Admin;

use App\DataFixtures\ORM\LoadIdeaData;
use App\Entity\IdeasWorkshop\Idea;
use App\Repository\IdeasWorkshop\IdeaRepository;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 * @group admin
 */
class AdminIdeaControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    /** @var IdeaRepository $ideaRepository */
    private $ideaRepository;

    public function testDisableAction(): void
    {
        /** @var Idea $idea */
        $idea = $this->ideaRepository->findOneByUuid(LoadIdeaData::IDEA_01_UUID);

        $this->assertTrue($idea->isEnabled());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(Request::METHOD_GET, sprintf('/admin/ideasworkshop-idea/%s/disable', $idea->getUuid()));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->get('doctrine.orm.entity_manager')->clear();

        $idea = $this->ideaRepository->findOneByUuid(LoadIdeaData::IDEA_01_UUID, true);

        $this->assertFalse($idea->isEnabled());
    }

    public function testEnableAction(): void
    {
        /** @var Idea $idea */
        $idea = $this->ideaRepository->findOneByUuid(LoadIdeaData::IDEA_08_UUID, true);

        $this->assertFalse($idea->isEnabled());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(Request::METHOD_GET, sprintf('/admin/ideasworkshop-idea/%s/enable', $idea->getUuid()));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->get('doctrine.orm.entity_manager')->clear();

        $idea = $this->ideaRepository->findOneByUuid(LoadIdeaData::IDEA_08_UUID);

        $this->assertTrue($idea->isEnabled());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();

        $this->ideaRepository = $this->getIdeaRepository();
    }

    protected function tearDown(): void
    {
        $this->kill();

        $this->ideaRepository = null;

        parent::tearDown();
    }
}
