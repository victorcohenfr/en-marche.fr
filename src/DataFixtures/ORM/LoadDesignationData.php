<?php

namespace App\DataFixtures\ORM;

use App\Entity\VotingPlatform\Designation\Designation;
use App\VotingPlatform\Designation\DesignationTypeEnum;
use App\VotingPlatform\Designation\DesignationZoneEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDesignationData extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Committee designation with started CANDIDATURE period in France
        $designation = new Designation('Désignation avec les candidatures ouvertes');
        $designation->setZones([DesignationZoneEnum::FRANCE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('+1 week'));
        $designation->setVoteStartDate(new \DateTime('+1 week'));
        $designation->setVoteEndDate(new \DateTime('+4 week'));

        $this->setReference('designation-1', $designation);
        $manager->persist($designation);

        // Committee designation with started VOTE period
        $designation = new Designation('Désignation avec les votes ouverts');
        $designation->setZones([DesignationZoneEnum::FRANCE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('-2 hours'));
        $designation->setVoteStartDate(new \DateTime('-1 hour'));
        $designation->setVoteEndDate(new \DateTime('+4 week'));

        $this->setReference('designation-2', $designation);
        $manager->persist($designation);

        // Committee designation with started RESULT period
        $designation = new Designation('Désignation avec les résultats disponibles');
        $designation->setZones([DesignationZoneEnum::FRANCE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('-1 week'));
        $designation->setVoteStartDate(new \DateTime('-6 days'));
        $designation->setVoteEndDate(new \DateTime('-1 hour'));

        $this->setReference('designation-3', $designation);
        $manager->persist($designation);

        // Archived Committee designation
        $designation = new Designation('Désignation archivée');
        $designation->setZones([DesignationZoneEnum::FRANCE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-6 months'));
        $designation->setCandidacyEndDate(new \DateTime('-5 months'));
        $designation->setVoteStartDate(new \DateTime('-5 months'));
        $designation->setVoteEndDate(new \DateTime('-4 months'));

        $this->setReference('designation-4', $designation);
        $manager->persist($designation);

        // Committee designation with started CANDIDATURE period in FDE
        $designation = new Designation('Désignation en cours à l\'étranger');
        $designation->setZones([DesignationZoneEnum::FDE]);
        $designation->setType(DesignationTypeEnum::COMMITTEE_ADHERENT);
        $designation->setCandidacyStartDate(new \DateTime('-1 day'));

        $this->setReference('designation-5', $designation);
        $manager->persist($designation);

        // COPOL designation with started CANDIDATURE period
        $designation = new Designation('Désignation COPOL avec les candidatures ouvertes');
        $designation->setType(DesignationTypeEnum::COPOL);
        $designation->addReferentTag($this->getReference('referent_tag_department_75'));
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));

        $this->setReference('designation-6', $designation);
        $manager->persist($designation);

        // COPOL designation with started VOTE period
        $designation = new Designation('Désignation COPOL les votes');
        $designation->setType(DesignationTypeEnum::COPOL);
        $designation->addReferentTag($this->getReference('referent_tag_department_92'));
        $designation->setCandidacyStartDate(new \DateTime('-1 month'));
        $designation->setCandidacyEndDate(new \DateTime('-2 hours'));
        $designation->setVoteStartDate(new \DateTime('-1 hour'));
        $designation->setVoteEndDate(new \DateTime('+4 week'));
        $designation->markAsLimited();

        $this->setReference('designation-7', $designation);
        $manager->persist($designation);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LoadReferentTagData::class,
        ];
    }
}
