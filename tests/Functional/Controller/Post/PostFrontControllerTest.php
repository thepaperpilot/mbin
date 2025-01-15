<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Post;

use App\DTO\ModeratorDto;
use App\Service\MagazineManager;
use App\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PostFrontControllerTest extends WebTestCase
{
    public function testFrontPage(): void
    {
        $this->client = $this->prepareEntries();

        $this->client->request('GET', '/microblog');
        $this->assertSelectorTextContains('h1', 'Hot');

        $crawler = $this->client->request('GET', '/microblog/newest');

        $this->assertSelectorTextContains('.post header', 'JohnDoe');
        $this->assertSelectorTextContains('.post header', 'to acme');

        $this->assertSelectorTextContains('#header .active', 'Microblog');

        $this->assertcount(2, $crawler->filter('.post'));

        foreach ($this->getSortOptions() as $sortOption) {
            $crawler = $this->client->click($crawler->filter('.options__filter')->selectLink($sortOption)->link());
            $this->assertSelectorTextContains('.options__filter', $sortOption);
            $this->assertSelectorTextContains('h1', ucfirst($sortOption));
        }
    }

    public function testMagazinePage(): void
    {
        $this->client = $this->prepareEntries();

        $this->client->request('GET', '/m/acme/microblog');
        $this->assertSelectorTextContains('h2', 'Hot');

        $crawler = $this->client->request('GET', '/m/acme/microblog/newest');

        $this->assertSelectorTextContains('.post header', 'JohnDoe');
        $this->assertSelectorTextNotContains('.post header', 'to acme');

        $this->assertSelectorTextContains('.head-title', '/m/acme');
        $this->assertSelectorTextContains('#sidebar .magazine', 'acme');

        $this->assertSelectorTextContains('#header .active', 'Microblog');

        $this->assertcount(1, $crawler->filter('.post'));

        foreach ($this->getSortOptions() as $sortOption) {
            $crawler = $this->client->click($crawler->filter('.options__filter')->selectLink($sortOption)->link());
            $this->assertSelectorTextContains('.options__filter', $sortOption);
            $this->assertSelectorTextContains('h1', 'acme');
            $this->assertSelectorTextContains('h2', ucfirst($sortOption));
        }
    }

    public function testSubPage(): void
    {
        $this->client = $this->prepareEntries();

        $magazineManager = $this->magazineManager;
        $magazineManager->subscribe($this->getMagazineByName('acme'), $this->getUserByUsername('Actor'));

        $this->client->loginUser($this->getUserByUsername('Actor'));

        $this->client->request('GET', '/sub/microblog');
        $this->assertSelectorTextContains('h1', 'Hot');

        $crawler = $this->client->request('GET', '/sub/microblog/newest');

        $this->assertSelectorTextContains('.post header', 'JohnDoe');
        $this->assertSelectorTextContains('.post header', 'to acme');

        $this->assertSelectorTextContains('.head-title', '/sub');

        $this->assertSelectorTextContains('#header .active', 'Microblog');

        $this->assertcount(1, $crawler->filter('.post'));

        foreach ($this->getSortOptions() as $sortOption) {
            $crawler = $this->client->click($crawler->filter('.options__filter')->selectLink($sortOption)->link());
            $this->assertSelectorTextContains('.options__filter', $sortOption);
            $this->assertSelectorTextContains('h1', ucfirst($sortOption));
        }
    }

    public function testModPage(): void
    {
        $this->client = $this->prepareEntries();
        $admin = $this->getUserByUsername('admin', isAdmin: true);

        $magazineManager = $this->client->getContainer()->get(MagazineManager::class);
        $moderator = new ModeratorDto($this->getMagazineByName('acme'));
        $moderator->user = $this->getUserByUsername('Actor');
        $moderator->addedBy = $admin;
        $magazineManager->addModerator($moderator);

        $this->client->loginUser($this->getUserByUsername('Actor'));

        $this->client->request('GET', '/mod/microblog');
        $this->assertSelectorTextContains('h1', 'Hot');

        $crawler = $this->client->request('GET', '/mod/microblog/newest');

        $this->assertSelectorTextContains('.post header', 'JohnDoe');
        $this->assertSelectorTextContains('.post header', 'to acme');

        $this->assertSelectorTextContains('.head-title', '/mod');

        $this->assertSelectorTextContains('#header .active', 'Microblog');

        $this->assertcount(1, $crawler->filter('.post'));

        foreach ($this->getSortOptions() as $sortOption) {
            $crawler = $this->client->click($crawler->filter('.options__filter')->selectLink($sortOption)->link());
            $this->assertSelectorTextContains('.options__filter', $sortOption);
            $this->assertSelectorTextContains('h1', ucfirst($sortOption));
        }
    }

    public function testFavPage(): void
    {
        $this->client = $this->prepareEntries();

        $favouriteManager = $this->favouriteManager;
        $favouriteManager->toggle($this->getUserByUsername('Actor'), $this->createPost('test post 3'));

        $this->client->loginUser($this->getUserByUsername('Actor'));

        $this->client->request('GET', '/fav/microblog');
        $this->assertSelectorTextContains('h1', 'Hot');

        $crawler = $this->client->request('GET', '/fav/microblog/newest');

        $this->assertSelectorTextContains('.post header', 'JohnDoe');
        $this->assertSelectorTextContains('.post header', 'to acme');

        $this->assertSelectorTextContains('.head-title', '/fav');

        $this->assertSelectorTextContains('#header .active', 'Microblog');

        $this->assertcount(1, $crawler->filter('.post'));

        foreach ($this->getSortOptions() as $sortOption) {
            $crawler = $this->client->click($crawler->filter('.options__filter')->selectLink($sortOption)->link());
            $this->assertSelectorTextContains('.options__filter', $sortOption);
            $this->assertSelectorTextContains('h1', ucfirst($sortOption));
        }
    }

    private function prepareEntries(): KernelBrowser
    {
        $this->createPost(
            'test post 1',
            $this->getMagazineByName('kbin', $this->getUserByUsername('JaneDoe')),
            $this->getUserByUsername('JaneDoe')
        );

        $this->createPost('test post 2');

        return $this->client;
    }

    private function getSortOptions(): array
    {
        return ['Top', 'Hot', 'Newest', 'Active', 'Commented'];
    }
}
