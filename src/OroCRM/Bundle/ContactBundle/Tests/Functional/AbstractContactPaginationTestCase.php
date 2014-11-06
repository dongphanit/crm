<?php

namespace OroCRM\Bundle\ContactBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\ContactBundle\Tests\Functional\DataFixtures\LoadContactEntitiesData;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class AbstractContactPaginationTestCase extends WebTestCase
{
    /**
     * @var array
     */
    protected $gridParams         = [
        'contacts-grid' =>
            'i=1&p=25&s%5BlastName%5D=-1&s%5BfirstName%5D=-1'
    ];

    /**
     * @var array
     */
    protected $gridParamsFiltered = [
        'contacts-grid' =>
            'i=1&p=25&s%5BlastName%5D=-1&s%5BfirstName%5D=-1&f%5BfirstName%5D%5Bvalue%5D=f&f%5BfirstName%5D%5Btype%5D=1'
    ];

    protected function setUp()
    {
        LoadContactEntitiesData::$owner = LoadUserData::USER_NAME;
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroCRM\Bundle\ContactBundle\Tests\Functional\DataFixtures\LoadUserData']);
        $this->loadFixtures(['OroCRM\Bundle\ContactBundle\Tests\Functional\DataFixtures\LoadContactEntitiesData']);
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadUserData::USER_NAME,
                LoadUserData::USER_PASSWORD
            )
        );
    }

    /**
     * @param array $params
     */
    protected function assertContactEntityGrid($params = [])
    {
        $this->client->request('GET', $this->getUrl('orocrm_contact_index', $params));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @param string $route
     * @param string $name
     * @param array $gridParams
     * @return Crawler
     */
    protected function openEntity($route, $name, array $gridParams)
    {
        return $this->client->request(
            'GET',
            $this->getUrl(
                $route,
                [
                    'id' => $this->getContactByName($name)->getId(),
                    'grid' => $gridParams
                ]
            )
        );
    }

    /**
     * @param string $name
     * @return Contact
     */
    protected function getContactByName($name)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroCRMContactBundle:Contact')
            ->findOneBy(['firstName' => $name]);
    }

    /**
     * @param Crawler $crawler
     * @param string $name
     */
    protected function assertCurrentContactName(Crawler $crawler, $name)
    {
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($name, $crawler->filter('h1.user-name')->html());
    }

    /**
     * @param Crawler $crawler
     * @param bool $isFirst
     * @param bool $isLast
     */
    protected function assertPositionEntityLinks(Crawler $crawler, $isFirst = false, $isLast = false)
    {
        $showFirst = !$isFirst;
        $showPrev  = !$isFirst;
        $showLast  = !$isLast;
        $showNext  = !$isLast;

        $this->assertEquals((int)$showFirst, $crawler->filter('.entity-pagination a:contains("First")')->count());
        $this->assertEquals((int)$showPrev, $crawler->filter('.entity-pagination a:contains("Prev")')->count());
        $this->assertEquals((int)$showNext, $crawler->filter('.entity-pagination a:contains("Next")')->count());
        $this->assertEquals((int)$showLast, $crawler->filter('.entity-pagination a:contains("Last")')->count());
    }

    /**
     * @param Crawler $crawler
     * @param string $position
     */
    protected function assertPositionEntity(Crawler $crawler, $position)
    {
        $this->assertEquals($position, $crawler->filter('.entity-pagination span')->html());
    }

    /**
     * @param Crawler $crawler
     */
    protected function checkPaginationLinks(Crawler $crawler)
    {
        $this->assertCurrentContactName($crawler, LoadContactEntitiesData::FIRST_ENTITY_NAME);
        $this->assertPositionEntityLinks($crawler, true);
        $this->assertPositionEntity($crawler, '1 of 4');

        // click next link
        $next = $crawler->filter('.entity-pagination a:contains("Next")')->link();
        $crawler = $this->client->click($next);

        $this->assertCurrentContactName($crawler, LoadContactEntitiesData::SECOND_ENTITY_NAME);
        $this->assertPositionEntityLinks($crawler);
        $this->assertPositionEntity($crawler, '2 of 4');

        // click last link
        $last = $crawler->filter('.entity-pagination a:contains("Last")')->link();
        $crawler = $this->client->click($last);

        $this->assertCurrentContactName($crawler, LoadContactEntitiesData::FOURTH_ENTITY_NAME);
        $this->assertPositionEntityLinks($crawler, false, true);
        $this->assertPositionEntity($crawler, '4 of 4');

        // click previous link
        $previous = $crawler->filter('.entity-pagination a:contains("Prev")')->link();
        $crawler = $this->client->click($previous);

        $this->assertCurrentContactName($crawler, LoadContactEntitiesData::THIRD_ENTITY_NAME);
        $this->assertPositionEntityLinks($crawler);
        $this->assertPositionEntity($crawler, '3 of 4');

        // click first link
        $first = $crawler->filter('.entity-pagination a:contains("First")')->link();
        $crawler = $this->client->click($first);

        $this->assertCurrentContactName($crawler, LoadContactEntitiesData::FIRST_ENTITY_NAME);
        $this->assertPositionEntityLinks($crawler, true);
        $this->assertPositionEntity($crawler, '1 of 4');
    }
}
