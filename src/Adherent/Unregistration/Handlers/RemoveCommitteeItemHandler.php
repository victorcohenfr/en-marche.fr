<?php

namespace App\Adherent\Unregistration\Handlers;

use App\Entity\Adherent;
use App\Repository\CommitteeFeedItemRepository;

class RemoveCommitteeItemHandler implements UnregistrationAdherentHandlerInterface
{
    private $repository;

    public function __construct(CommitteeFeedItemRepository $repository)
    {
        $this->repository = $repository;
    }

    public function supports(Adherent $adherent): bool
    {
        return true;
    }

    public function handle(Adherent $adherent): void
    {
        $this->repository->removeAuthorItems($adherent);
    }
}
