<?php

namespace App\Tests\Unit\Controller;

use App\Controller\SortieController;
use App\Entity\Sortie;
use App\Repository\GroupePriveRepository;
use App\Repository\LieuRepository;
use App\Utils\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Service\SortieService;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SortieControllerTest extends WebTestCase
{
    private $client;
    private $sortieServiceMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();

// Mock du service SortieService
        $sortieServiceMock = $this->createMock(SortieService::class);
        $sortieServiceMock->method('getFilteredSorties')
            ->willReturn([
                'sorties' => [], // tableau vide pour le test
                'lieux' => [],
                'dateDebut' => null,
                'dateFin' => null
            ]);

// Injecter le mock dans le container Symfony
        self::getContainer()->set(SortieService::class, $sortieServiceMock);
    }

    /** Teste que la page index est accessible et retourne 200 */
    public function testIndexPageAccessible(): void
    {
        $this->client->request('GET', '/sortie');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body'); // On vérifie juste qu’on a du HTML
    }
}

