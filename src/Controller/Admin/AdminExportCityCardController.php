<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Election\CityResultAggregator;
use AppBundle\Repository\Election\CityCardRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\IteratorCallbackSourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminExportCityCardController extends Controller
{
    private $aggregator;
    private $cityCardRepository;
    private $exporter;

    public function __construct(CityResultAggregator $aggregator, CityCardRepository $repository, Exporter $exporter)
    {
        $this->aggregator = $aggregator;
        $this->cityCardRepository = $repository;
        $this->exporter = $exporter;
    }

    /**
     * @Route("/app/election-citycard/export-all", name="admin_city_card_export_all", methods={"GET"})
     *
     * @Security("is_granted('ROLE_ADMIN_ELECTION_CITY_CARD')")
     */
    public function exportCityCardsAction(string $_format = 'csv'): Response
    {
        return $this->exporter->getResponse(
            $_format,
            'export-villes--'.date('d-m-Y--H-i').'.'.$_format,
            new IteratorCallbackSourceIterator($this->cityCardRepository->getIterator(), function (array $cityCard) {
                $cityCard = $cityCard[0];
                $city = $cityCard->getCity();
                $results = $this->aggregator->getResults($cityCard->getCity());

                return [
                    'INSEE' => $city->getInseeCode(),
                    'Commune' => $city->getName(),
                    'Département' => $city->getDepartment()->getName(),
                    'Région' => $city->getDepartment()->getRegion()->getName(),
                ];
            })
        );
    }
}
