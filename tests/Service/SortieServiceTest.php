<?php

namespace App\Tests\Unit\Service;

use App\Entity\Sortie;
use App\Entity\Participant;
use App\Enum\Etat;
use App\Service\SortieService;
use App\Repository\SortieRepository;
use App\Repository\LieuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use PHPUnit\Framework\TestCase;

class SortieServiceTest extends TestCase
{
    private $entityManager;
    private $sortieRepository;
    private $lieuRepository;
    private $security;
    private $service;

    protected function setUp(): void
    {
        // Mock des dépendances
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->sortieRepository = $this->createMock(SortieRepository::class);
        $this->lieuRepository = $this->createMock(LieuRepository::class);
        $this->security = $this->createMock(Security::class);

        // Instance du service à tester
        $this->service = new SortieService(
            $this->entityManager,
            $this->sortieRepository,
            $this->lieuRepository,
            $this->security
        );
    }

    // Teste que la méthode publier() met bien l'état de la sortie à OPEN
    public function testPublierMetEtatOpen(): void
    {
        $sortie = new Sortie();
        $sortie->setEtat(Etat::CREATED);

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->publier($sortie);

        $this->assertEquals(Etat::OPEN, $sortie->getEtat());
    }

    // Teste que la méthode inscrireParticipant() ajoute correctement un participant si la sortie est ouverte
    public function testInscrireParticipantAjouteParticipant(): void
    {
        $participant = new Participant();
        $sortie = new Sortie();
        $sortie->setEtat(Etat::OPEN);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->inscrireParticipant($sortie, $participant);

        $this->assertTrue($result);
        $this->assertTrue($sortie->getParticipants()->contains($participant));
    }

    // Teste que la méthode inscrireParticipant() refuse d'ajouter un participant déjà inscrit
    public function testInscrireParticipantRefuseSiDejaInscrit(): void
    {
        $participant = new Participant();
        $sortie = new Sortie();
        $sortie->setEtat(Etat::OPEN);
        $sortie->addParticipant($participant);

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->inscrireParticipant($sortie, $participant);

        $this->assertFalse($result);
    }

    // Teste que la méthode removeParticipant() retire correctement un participant si présent dans la sortie
    public function testRemoveParticipantRetireCorrectement(): void
    {
        $participant = new Participant();
        $sortie = new Sortie();
        $sortie->setEtat(Etat::OPEN);
        $sortie->addParticipant($participant);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->removeParticipant($sortie, $participant);

        $this->assertTrue($result);
        $this->assertFalse($sortie->getParticipants()->contains($participant));
    }

    // Teste que la méthode validerSortie() refuse la validation si l'utilisateur n'est pas connecté
    public function testValiderSortieRefuseSiNonConnecte(): void
    {
        $sortie = new Sortie();
        $result = $this->service->validerSortie($sortie, null);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('connecté', $result['message']);
    }

    // Teste que la méthode deleteSortie() refuse d'annuler une sortie si le motif est manquant
    public function testDeleteSortieRefuseSiMotifManquant(): void
    {
        $user = $this->createMock(Participant::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $sortie = new Sortie();
        $sortie->setEtat(Etat::OPEN);
        $sortie->setOrganisateur($user);
        $sortie->setDateHeureDebut((new \DateTimeImmutable('+2 days')));

        $result = $this->service->deleteSortie($sortie, $user, '');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('motif', $result['message']);
    }

    // Teste que la validation d'une sortie échoue si l'utilisateur n'est pas organisateur ni admin
    public function testValiderSortieEchoueSiUtilisateurNonOrganisateurNiAdmin(): void
    {
        $user = $this->createMock(Participant::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $sortie = new Sortie();
        $sortie->setEtat(Etat::CREATED);
        $sortie->setOrganisateur($this->createMock(Participant::class));

        $result = $this->service->validerSortie($sortie, $user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('autorisé', $result['message']);
    }

    // Teste que la validation d'une sortie échoue si la date de début est passée
    public function testValiderSortieEchoueSiDateDejaPassee(): void
    {
        $user = $this->createMock(Participant::class);
        $user->method('getRoles')->willReturn(['ROLE_ADMIN']);

        $sortie = new Sortie();
        $sortie->setEtat(Etat::CREATED);
        $sortie->setDateHeureDebut(new \DateTimeImmutable('-1 day'));

        $result = $this->service->validerSortie($sortie, $user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('déjà passée', $result['message']);
    }

    // Test que la validation d'une sortie réussit si tout est valide
    public function testValiderSortieReussitSiToutEstValide(): void
    {
        $user = $this->createMock(Participant::class);
        $user->method('getRoles')->willReturn(['ROLE_ADMIN']);

        $sortie = new Sortie();
        $sortie->setEtat(Etat::CREATED);
        $sortie->setNom('Sortie test');
        $sortie->setLieu($this->createMock(\App\Entity\Lieu::class));
        $sortie->setDateHeureDebut(new \DateTimeImmutable('+2 days'));
        $sortie->setDateLimiteInscription(new \DateTimeImmutable('+1 day'));

        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('persist')->with($sortie);

        $result = $this->service->validerSortie($sortie, $user);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('publiée', $result['message']);
        $this->assertEquals(Etat::OPEN, $sortie->getEtat());
    }

    // Teste que l'annulation d'une sortie réussit si l’utilisateur est admin et motif fourni
    public function testDeleteSortieReussitSiAdminEtMotif(): void
    {
        $user = $this->createMock(Participant::class);
        $user->method('getRoles')->willReturn(['ROLE_ADMIN']);

        $sortie = new Sortie();
        $sortie->setEtat(Etat::OPEN);
        $sortie->setDateHeureDebut(new \DateTimeImmutable('+2 days'));

        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('persist')->with($sortie);

        $result = $this->service->deleteSortie($sortie, $user, 'Annulation admin');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('annulée', $result['message']);
        $this->assertEquals(Etat::CANCELLED, $sortie->getEtat());
        $this->assertEquals('Annulation admin', $sortie->getMotifAnnulation());
    }

    // Test que l'inscription d'un participant échoue si la sortie n’est pas ouverte
    public function testInscrireParticipantRefuseSiSortieFermee(): void
    {
        $participant = new Participant();
        $sortie = new Sortie();
        $sortie->setEtat(Etat::CLOSED);

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->inscrireParticipant($sortie, $participant);

        $this->assertFalse($result);
        $this->assertFalse($sortie->getParticipants()->contains($participant));
    }
}
