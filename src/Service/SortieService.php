<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Enum\Etat;
use App\Repository\LieuRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

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

    public function getFilteredSorties(Request $request): array
    {
        // Récupération des filtres
        $nomRecherche = $request->query->get('nom_sortie');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        $organisateur = $request->query->get('organisateur');
        $inscrit = $request->query->get('inscrit');
        $nonInscrit = $request->query->get('non_inscrit');
        $passees = $request->query->get('passees');

        $user = $this->security->getUser();

        // Requêtes en BDD
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

}