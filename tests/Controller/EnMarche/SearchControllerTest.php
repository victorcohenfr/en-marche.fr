<?php

namespace Tests\App\Controller\EnMarche;

use App\Entity\Event;
use App\Search\SearchParametersFilter;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 * @group controller
 */
class SearchControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    /**
     * @dataProvider provideQuery
     */
    public function testIndex($query)
    {
        $this->client->request(Request::METHOD_GET, '/recherche', $query);

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
    }

    /**
     * @dataProvider providerPathSearchPage
     */
    public function testAccessSearchPage(string $path)
    {
        $this->client->request(Request::METHOD_GET, $path);

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
    }

    public function providerPathSearchPage()
    {
        return [
            ['/evenements'],
            ['/comites'],
            ['/recherche'],
        ];
    }

    public function provideQuery()
    {
        yield 'No criteria' => [[]];
        yield 'Search committees' => [[SearchParametersFilter::PARAMETER_TYPE => SearchParametersFilter::TYPE_COMMITTEES]];
        yield 'Search events' => [[SearchParametersFilter::PARAMETER_TYPE => SearchParametersFilter::TYPE_EVENTS]];
        yield 'Search citizen projects' => [[SearchParametersFilter::PARAMETER_TYPE => SearchParametersFilter::TYPE_CITIZEN_PROJECTS]];
    }

    public function testListAllEvents()
    {
        /** @var Paginator $evenets */
        $events = $this->getRepository(Event::class)->paginate();

        $this->client->request(Request::METHOD_GET, '/tous-les-evenements/3');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->request(Request::METHOD_GET, '/tous-les-evenements/1');

        $this->assertSame($events->count(), $crawler->filter('div.search__results__row')->count());
        $this->assertSame(0, $crawler->filter('meta[rel="prev"]')->count());
        $this->assertSame(0, $crawler->filter('meta[rel="next"]')->count());
        $this->assertSame(1, $crawler->filter('.listing__paginator li')->count());
        $this->assertSame('/tous-les-evenements', $crawler->filter('.listing__paginator li a')->attr('href'));
        $this->assertSame('1', trim($crawler->filter('.listing__paginator li a')->text()));
    }

    public function testListEventsByCategory()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/evenements');

        $this->assertSame(7, $crawler->filter('div.search__results__row')->count());
        $this->assertSame(2, $crawler->filter('.search__results__row .search__results__meta span:contains("Jacques Picard")')->count());
        $this->assertSame(1, $crawler->filter('.search__results__row .search__results__meta span:contains("Referent75and77 Referent75and77")')->count());

        $crawler = $this->client->request(Request::METHOD_GET, '/evenements/categorie/conference-debat');

        $this->assertSame(1, $crawler->filter('div.search__results__row')->count());
        $this->assertSame('Conférence-débat', $crawler->filter('.search__results__info .search__results__tag div')->text());
        $this->assertSame('Réunion de réflexion évryenne', trim($crawler->filter('.search__results__info .search__results__meta h2 a')->text()));

        $this->client->request(Request::METHOD_GET, '/evenements/categorie/inexistante');
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());
        $this->assertClientIsRedirectedTo('/evenements', $this->client);
    }

    public function testListReferentEvents()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/evenements');

        $this->assertSame(7, $crawler->filter('div.search__results__row')->count());

        $crawler = $this->client->request(Request::METHOD_GET, '/evenements?re=true');

        $this->assertSame(1, $crawler->filter('div.search__results__row')->count());
        $this->assertSame(1, $crawler->filter('.search__results__row .search__results__meta span:contains("Referent75and77 Referent75and77")')->count());
    }

    public function testListAllCommittee()
    {
        $this->client->request(Request::METHOD_GET, '/tous-les-comites/3');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->request(Request::METHOD_GET, '/tous-les-comites/1');

        $this->assertSame(9, $crawler->filter('.search__committee__box')->count());
        $this->assertSame(0, $crawler->filter('meta[rel="prev"]')->count());
        $this->assertSame(0, $crawler->filter('meta[rel="next"]')->count());
        $this->assertSame(1, $crawler->filter('.listing__paginator li')->count());
        $this->assertSame('/tous-les-comites', $crawler->filter('.listing__paginator li a')->attr('href'));
        $this->assertSame('1', trim($crawler->filter('.listing__paginator li a')->text()));
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
