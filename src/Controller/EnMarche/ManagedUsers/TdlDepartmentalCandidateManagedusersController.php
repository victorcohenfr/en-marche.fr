<?php

namespace App\Controller\EnMarche\ManagedUsers;

use App\Form\ManagedUsers\CandidateManagedUsersFilterType;
use App\Form\ManagedUsers\ReferentManagedUsersFilterType;
use App\ManagedUsers\ManagedUsersFilter;
use App\Subscription\SubscriptionTypeEnum;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/espace-tdl-departementale", name="app_tdl_departmental_candidate_managed_users_", methods={"GET"})
 *
 * @Security("is_granted('ROLE_TDL_DEPARTMENTAL_CANDIDATE')")
 */
class TdlDepartmentalCandidateManagedusersController extends AbstractManagedUsersController
{
    private const SPACE_NAME = 'tdl_departmental_candidate';

    protected function getSpaceType(): string
    {
        return self::SPACE_NAME;
    }

    protected function createFilterForm(ManagedUsersFilter $filter = null): FormInterface
    {
        return $this->createForm(CandidateManagedUsersFilterType::class, $filter, [
            'method' => Request::METHOD_GET,
            'csrf_protection' => false,
        ]);
    }

    protected function createFilterModel(Request $request): ManagedUsersFilter
    {
        $session = $request->getSession();

        return new ManagedUsersFilter(
            SubscriptionTypeEnum::TDL_DEPARTMENTAL_CANDIDATE_EMAIL,
            $this->getMainUser($session)->getManagedArea()->getTags()->toArray()
        );
    }
}
