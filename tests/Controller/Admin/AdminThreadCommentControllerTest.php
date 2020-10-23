<?php

namespace Tests\App\Controller\Admin;

use App\DataFixtures\ORM\LoadIdeaThreadCommentData;
use App\Entity\IdeasWorkshop\ThreadComment;
use App\Repository\ThreadCommentRepository;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 * @group admin
 */
class AdminThreadCommentControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    /** @var ThreadCommentRepository $threadCommentRepository */
    private $threadCommentRepository;

    public function testDisableAction(): void
    {
        /** @var ThreadComment $threadComment */
        $threadComment = $this->threadCommentRepository->findOneByUuid(LoadIdeaThreadCommentData::THREAD_COMMENT_01_UUID);

        $this->assertTrue($threadComment->isEnabled());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(Request::METHOD_GET, sprintf('/admin/ideasworkshop-threadcomment/%s/disable', $threadComment->getUuid()));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->get('doctrine.orm.entity_manager')->clear();

        $threadComment = $this->threadCommentRepository->findOneByUuid(LoadIdeaThreadCommentData::THREAD_COMMENT_01_UUID, true);

        $this->assertFalse($threadComment->isEnabled());
    }

    public function testEnableAction(): void
    {
        /** @var ThreadComment $threadComment */
        $threadComment = $this->threadCommentRepository->findOneByUuid(LoadIdeaThreadCommentData::THREAD_COMMENT_10_UUID, true);

        $this->assertFalse($threadComment->isEnabled());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(Request::METHOD_GET, sprintf('/admin/ideasworkshop-threadcomment/%s/enable', $threadComment->getUuid()));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->get('doctrine.orm.entity_manager')->clear();

        $threadComment = $this->threadCommentRepository->findOneByUuid(LoadIdeaThreadCommentData::THREAD_COMMENT_10_UUID);

        $this->assertTrue($threadComment->isEnabled());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();

        $this->threadCommentRepository = $this->getThreadCommentRepository();
    }

    protected function tearDown(): void
    {
        $this->kill();

        $this->threadCommentRepository = null;

        parent::tearDown();
    }
}
