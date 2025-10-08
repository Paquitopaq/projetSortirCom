<?php

namespace App\Service;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Repository\LieuRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class SortieService
{

    private SortieRepository $sortieRepository;
    private LieuRepository $lieuRepository;
    private Security $security;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        SortieRepository $sortieRepository,
        LieuRepository $lieuRepository,
        Security $security
    )
    {
        $this->sortieRepository = $sortieRepository;
        $this->lieuRepository = $lieuRepository;
        $this->security = $security;
    }
    public function createSortie(Sortie $sortie, bool $publier): void
    {
        if ($publier) {
            $sortie->setEtat(Etat::OPEN);
        } else {
            $sortie->setEtat(Etat::CREATED);
        }

        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }

    public function publier(Sortie $sortie): void
    {

        $sortie->setEtat(Etat::OPEN);
        $this->entityManager->flush();
    }

    public function annulerSortie(Sortie $sortie, ?UserInterface $user): array
    {
        if (method_exists($sortie, 'getOrganisateur')) {
            if ($sortie->getOrganisateur() !== $user) {
                return [
                    'success' => false,
                    'message' => 'Vous n’êtes pas autorisé à annuler cette sortie.',
                ];
            }
        }

        if ($sortie->getDateHeureDebut() <= new \DateTime()) {
            return [
                'success' => false,
                'message' => 'Impossible d’annuler une sortie déjà commencée ou passée.',
            ];
        }

        $sortie->setEtat(Etat::CANCELLED);

        // 4. Sauvegarder
        $this->em->persist($sortie);
        $this->em->flush();

        return [
            'success' => true,
            'message' => 'La sortie a bien été annulée.',
        ];
    }


    public function getFilteredSorties(Request $request): array
    {
        $nomRecherche = $request->query->get('nom_sortie');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        $organisateur = $request->query->get('organisateur');
        $inscrit = $request->query->get('inscrit');
        $nonInscrit = $request->query->get('non_inscrit');
        $passees = $request->query->get('passees');

        $user = $this->security->getUser();

        $lieux = $this->lieuRepository->findAll();

        $sorties = $this->sortieRepository->findByFilters(
            $nomRecherche,
            $dateDebut,
            $dateFin,
            $organisateur,
            $inscrit,
            $nonInscrit,
            $passees,
            $user
        );

        return [
            'sorties' => $sorties,
            'lieux' => $lieux,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ];
    }
    public function inscrireParticipant(Sortie $sortie, Participant $participant): bool {

        // TODO Ajouter des messages d'erreurs clair
        if ($sortie->getEtat() !== Etat::OPEN) {
            return false;
        }

        if ($sortie->getParticipants()->contains($participant)) {
            return false;
        }

        if ($sortie->getNbInscrits()>= $sortie->getNbInscriptionMax()) {
            return false;
        }

        $sortie->addParticipant($participant);
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();

        return true;
    }

    public function removeParticipant(Sortie $sortie, Participant $participant): void {
        $sortie->removeParticipant($participant);
    }

}