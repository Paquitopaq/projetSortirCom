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
        private EntityManagerInterface $entityManager,
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

        $participant = $this->entityManager->getRepository(Participant::class)->find($this->security->getUser()->getId());
        $sortie->setOrganisateur($participant);

        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }

    public function publier(Sortie $sortie): void
    {
        $sortie->setEtat(Etat::OPEN);
        $this->entityManager->flush();
    }

    public function validerSortie(Sortie $sortie, ?UserInterface $user): array
    {
        // Vérifier que l'utilisateur existe
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Vous devez être connecté pour valider une sortie.',
            ];
        }

        // Vérifier que l'utilisateur est l'organisateur ou admin
        $isOrganisateur = method_exists($sortie, 'getOrganisateur') && $sortie->getOrganisateur() === $user;
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        if (!$isOrganisateur && !$isAdmin) {
            return [
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à valider cette sortie.',
            ];
        }

        // Vérifier que la sortie est en état "Créée"
        if ($sortie->getEtat() !== Etat::CREATED) {
            return [
                'success' => false,
                'message' => 'Seules les sorties en état "Créée" peuvent être publiées.',
            ];
        }

        // Vérifier que la date de début est dans le futur
        if ($sortie->getDateHeureDebut() <= new \DateTime()) {
            return [
                'success' => false,
                'message' => 'Impossible de publier une sortie dont la date est déjà passée.',
            ];
        }

        // Vérifier que la date limite d'inscription est avant la date de début
        if ($sortie->getDateLimiteInscription() >= $sortie->getDateHeureDebut()) {
            return [
                'success' => false,
                'message' => 'La date limite d\'inscription doit être avant la date de début de la sortie.',
            ];
        }

        // Vérifier que la sortie a au moins les informations minimales
        if (!$sortie->getNom() || !$sortie->getLieu()) {
            return [
                'success' => false,
                'message' => 'La sortie doit avoir un nom et un lieu pour être publiée.',
            ];
        }

        // Publier la sortie
        $sortie->setEtat(Etat::OPEN);
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'La sortie a été publiée avec succès. Les participants peuvent maintenant s\'inscrire.',
        ];
    }

    public function deleteSortie(Sortie $sortie, ?UserInterface $user, ?string $motif = null): array
    {
        // Vérifier que l'utilisateur existe
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Vous devez être connecté pour annuler une sortie.',
            ];
        }

        // Vérifier que l'utilisateur est l'organisateur ou admin
        $isOrganisateur = method_exists($sortie, 'getOrganisateur') && $sortie->getOrganisateur() === $user;
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        if (!$isOrganisateur && !$isAdmin) {
            return [
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à annuler cette sortie.',
            ];
        }

        // Vérifier que la sortie n'est pas déjà annulée
        if ($sortie->getEtat() === Etat::CANCELLED) {
            return [
                'success' => false,
                'message' => 'Cette sortie est déjà annulée.',
            ];
        }

        // Vérifier que la sortie n'est pas archivée
        if ($sortie->getEtat() === Etat::ARCHIVEE) {
            return [
                'success' => false,
                'message' => 'Impossible d\'annuler une sortie archivée.',
            ];
        }

        // Les admins peuvent annuler même les sorties commencées
        if (!$isAdmin) {
            // Pour les organisateurs : vérifier que la sortie n'est pas déjà commencée
            if ($sortie->getDateHeureDebut() <= new \DateTime()) {
                return [
                    'success' => false,
                    'message' => 'Impossible d\'annuler une sortie déjà commencée ou passée.',
                ];
            }
        }

        // Vérifier que le motif est fourni
        if (!$motif || trim($motif) === '') {
            return [
                'success' => false,
                'message' => 'Le motif d\'annulation est obligatoire.',
            ];
        }

        // Annuler la sortie
        $sortie->setEtat(Etat::CANCELLED);
        $sortie->setMotifAnnulation($motif);

        $this->entityManager->persist($sortie);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'La sortie a bien été annulée. Les participants ont été notifiés.',
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


        $participant = $this->entityManager->getRepository(Participant::class)->find($this->security->getUser()->getId());

        $sorties = $this->sortieRepository->findByFilters(
            $nomRecherche,
            $dateDebut,
            $dateFin,
            $organisateur,
            $inscrit,
            $nonInscrit,
            $passees,
            $participant
        );

        $lieux = $this->lieuRepository->findAll();

        return [
            'sorties' => $sorties,
            'lieux' => $lieux,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ];
    }

    public function inscrireParticipant(Sortie $sortie, Participant $participant): bool {

        if (!$sortie->getParticipants()->contains($participant)&&$sortie->getEtat() == Etat::OPEN) {
            $sortie->addParticipant($participant);
            $this->entityManager->persist($sortie);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function removeParticipant(Sortie $sortie, Participant $participant): bool {

        if ($sortie->getParticipants()->contains($participant) && ($sortie->getEtat() == Etat::OPEN || $sortie->getEtat() == Etat::CLOSED)) {
            $sortie->removeParticipant($participant);
            $this->entityManager->persist($sortie);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function archiverSorties(): void
    {
        $this->sortieRepository->archiverSortiesAnciennes();
    }

    public function clotureSorties(): void{
        $this->sortieRepository->clotureSortie();
    }
}