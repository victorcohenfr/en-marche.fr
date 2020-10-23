<?php

namespace Tests\App\Controller\Admin;

use App\Committee\CommitteeAdherentMandateManager;
use App\DataFixtures\ORM\LoadAdherentData;
use App\Mailer\Message\CommitteeApprovalConfirmationMessage;
use App\Mailer\Message\CommitteeApprovalReferentMessage;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 * @group admin
 */
class AdminCommitteeControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private $committeeRepository;

    public function testApproveAction(): void
    {
        $committee = $this->committeeRepository->findOneByUuid(LoadAdherentData::COMMITTEE_2_UUID);

        $this->assertFalse($committee->isApproved());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(Request::METHOD_GET, sprintf('/admin/committee/%s/approve', $committee->getId()));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->get('doctrine.orm.entity_manager')->clear();

        $committee = $this->committeeRepository->findOneByUuid(LoadAdherentData::COMMITTEE_2_UUID);

        $this->assertTrue($committee->isApproved());
        $this->assertCountMails(1, CommitteeApprovalConfirmationMessage::class, 'benjyd@aol.com');
        $this->assertCountMails(1, CommitteeApprovalReferentMessage::class, 'referent@en-marche-dev.fr');
    }

    /**
     * @dataProvider provideActions
     */
    public function testCannotChangeMandateIfCommitteeNotApprovedAction(string $action): void
    {
        $committee = $this->committeeRepository->findOneByUuid(LoadAdherentData::COMMITTEE_2_UUID);
        $adherent = $this->getAdherentRepository()->findOneByUuid(LoadAdherentData::ADHERENT_1_UUID);

        $this->assertFalse($committee->isApproved());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(
            Request::METHOD_GET,
            \sprintf('/admin/committee/%s/members/%s/%s-mandate', $committee->getId(), $adherent->getId(), $action)
        );
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST, $this->client->getResponse());
    }

    public function provideActions(): iterable
    {
        yield [CommitteeAdherentMandateManager::CREATE_ACTION];
        yield [CommitteeAdherentMandateManager::FINISH_ACTION];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();

        $this->committeeRepository = $this->getCommitteeRepository();
    }

    protected function tearDown(): void
    {
        $this->kill();

        $this->committeeRepository = null;

        parent::tearDown();
    }
}
