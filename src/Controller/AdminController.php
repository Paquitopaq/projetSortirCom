<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Form\DeleteSortieType;
use App\Form\ImportParticipantType;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use App\Service\AdminUserService;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        // Statistiques globales
        $participantRepo = $em->getRepository(Participant::class);
        $sortieRepo = $em->getRepository(Sortie::class);

        $nbUsers = $participantRepo->count([]);
        $nbUsersActifs = $participantRepo->count(['actif' => true]);
        $nbSorties = $sortieRepo->count([]);

        //Statistiques temporelles
        $now = new \DateTime();

        // Sorties à venir
        $nbSortiesAVenir = $sortieRepo->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.dateHeureDebut > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        // Sorties passées
        $nbSortiesPassees = $sortieRepo->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.dateHeureDebut <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        // Dernières activités
        $dernieresSorties = $sortieRepo->createQueryBuilder('s')
            ->orderBy('s.dateHeureDebut', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $derniersParticipants = $participantRepo->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC') // ou p.dateCreation si tu as ce champ
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $nbUsersInactifs = $nbUsers - $nbUsersActifs;

        return $this->render('admin/dashboard.html.twig', [
            'nbUsers' => $nbUsers,
            'nbUsersActifs' => $nbUsersActifs,
            'nbUsersInactifs' => $nbUsersInactifs,
            'nbSorties' => $nbSorties,
            'nbSortiesAVenir' => $nbSortiesAVenir,
            'nbSortiesPassees' => $nbSortiesPassees,
            'dernieresSorties' => $dernieresSorties,
            'derniersParticipants' => $derniersParticipants,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(EntityManagerInterface $em, ParticipantRepository $participantRepository, Request $request): Response
    {
        $participants = $participantRepository->findAll();
        $forms = [];

        foreach ($participants as $participant) {
            $form = $this->createForm(ProfilType::class, $participant, [
                'method' => 'POST',
                'action' => $this->generateUrl('admin_user_edit', ['id' => $participant->getId()])
            ]);
            $forms[$participant->getId()] = $form->createView();
        }

        return $this->render('admin/users.html.twig', [
            'participants' => $participants,
            'forms' => $forms,
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit', methods: ['POST'])]
    public function editUser(
        Participant $participant,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ProfilType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($participant);
            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/user/{id}/toggle', name: 'admin_user_desactived', methods: ['POST'])]
    public function toggleUser(Participant $participant, EntityManagerInterface $em): Response
    {
        $participant->setActif(!$participant->getActif());
        $em->persist($participant);
        $em->flush();

        $this->addFlash('success', $participant->getActif() ? 'Utilisateur activé avec succès.' : 'Utilisateur désactivé avec succès.');

        return $this->redirectToRoute('admin_users');
    }


    #[Route('/admin/user/{id}/toggle-admin', name: 'admin_user_toggle_admin', methods: ['POST'])]
    public function toggleAdmin(
        Participant $participant,
        AdminUserService $adminUserService
    ): Response {
        $adminUserService->toggleAdmin($participant);

        $this->addFlash(
            'success',
            in_array('ROLE_ADMIN', $participant->getRoles()) ? 'Utilisateur promu admin.' : 'Utilisateur rétrogradé.'
        );

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/sorties', name: 'admin_sorties')]
    public function sorties(EntityManagerInterface $em): Response
    {
        $sorties = $em->getRepository(Sortie::class)->findAll();

        return $this->render('admin/sorties.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/create', name: 'admin_create_profil')]
    public function createProfil(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserPasswordHasherInterface $passwordHasher): Response
    {
        $participant = new Participant();
        $form = $this->createForm(ProfilType::class, $participant, ['is_create' => true]);
        $form->handleRequest($request);

        $avatarDirectory = $this->getParameter('kernel.project_dir') . '/public/assets/avatars';
        $avatars = array_diff(scandir($avatarDirectory), ['..', '.']);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashedPassword);
            }

            $selectedAvatar = $request->request->get('selected_avatar');
            $photoFile = $form->get('photoProfil')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move($this->getParameter('photo_directory'), $newFilename);
                    $participant->setPhotoProfil($newFilename);
                    $participant->setPhotoSource('image');
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement de la photo.');
                }

            } elseif ($selectedAvatar) {
                $participant->setPhotoProfil($selectedAvatar);
                $participant->setPhotoSource('avatar');
            }

            $em->persist($participant);
            $em->flush();
            $this->addFlash('success', 'Profil créé avec succès.');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/createProfil.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
            'avatars' => $avatars,
        ]);
    }

    #[Route('/import/{id}/sortie', name: 'admin_import')]
    public function import(Request $request, Sortie $sortie, SortieService $sortieService, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ImportParticipantType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csvFile')->getData();

            if (($handle = fopen($csvFile->getPathname(), 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $email = $data[0];
                    $participant = $em->getRepository(Participant::class)->findOneBy(['email' => $email]);

                    if ($participant && $sortieService->inscrireParticipant($sortie, $participant)) {
                        $this->addFlash('success', "Inscription réussie pour {$email}");
                    } else {
                        $this->addFlash('danger', "Échec d'inscription pour {$email}");
                    }
                }
                fclose($handle);
                $em->flush();
            }

            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/import.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sortie/{id}/delete', name: 'sortie_delete_admin', methods: ['GET', 'POST'])]
    public function delete(
        Sortie $sortie,
        Request $request,
        SortieService $sortieService
    ): Response {
        $form = $this->createForm(DeleteSortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $motif = $form->get('motifAnnulation')->getData();
            $result = $sortieService->deleteSortie($sortie, $user, $motif);

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
            } else {
                $this->addFlash('danger', $result['message']);
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('sortie/delete.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(
        Participant $participant,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        if (!$this->isCsrfTokenValid('delete'.$participant->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action non autorisée.');
            return $this->redirectToRoute('admin_users');
        }

        // Archiver les sorties organisées par l'utilisateur
        $sortiesOrganisees = $em->getRepository(Sortie::class)->findBy(['organisateur' => $participant]);

        foreach ($sortiesOrganisees as $sortie) {
            $sortie->setEtat(\App\Enum\Etat::ARCHIVEE);
            $em->persist($sortie);
        }

        $em->remove($participant);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé et ses sorties archivées avec succès.');

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/participants/all', name: 'admin_get_all_participants')]
    public function getAllParticipants(EntityManagerInterface $em): Response
    {
        $participants = $em->getRepository(Participant::class)
            ->createQueryBuilder('p')
            ->where('p.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();

        $results = array_map(function(Participant $p) {
            return [
                'id' => $p->getId(),
                'email' => $p->getEmail(),
                'pseudo' => $p->getPseudo(),
                'nom' => $p->getNom(),
                'prenom' => $p->getPrenom(),
            ];
        }, $participants);

        return $this->json($results);
    }

    #[Route('/participants/search', name: 'admin_search_participants')]
    public function searchParticipants(Request $request, EntityManagerInterface $em): Response
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $participants = $em->getRepository(Participant::class)
            ->createQueryBuilder('p')
            ->where('p.email LIKE :query OR p.pseudo LIKE :query OR p.nom LIKE :query OR p.prenom LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = array_map(function(Participant $p) {
            return [
                'id' => $p->getId(),
                'email' => $p->getEmail(),
                'pseudo' => $p->getPseudo(),
                'nom' => $p->getNom(),
                'prenom' => $p->getPrenom(),
            ];
        }, $participants);

        return $this->json($results);
    }

    #[Route('/sortie/{id}/add-participant', name: 'admin_add_participant', methods: ['POST'])]
    public function addParticipant(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $em,
        SortieService $sortieService
    ): Response {
        $participantId = $request->request->get('participantId');

        if (!$participantId) {
            $this->addFlash('danger', 'Participant non spécifié.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        $participant = $em->getRepository(Participant::class)->find($participantId);

        if (!$participant) {
            $this->addFlash('danger', 'Participant introuvable.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        if ($sortie->getParticipants()->contains($participant)) {
            $this->addFlash('warning', 'Ce participant est déjà inscrit.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        if ($sortie->getParticipants()->count() >= $sortie->getNbInscriptionMax()) {
            $this->addFlash('danger', 'La sortie est complète.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        if ($sortie->getEtat()->value !== 'Ouverte') {
            $sortie->addParticipant($participant);
            $em->flush();
        } else {
            if (!$sortieService->inscrireParticipant($sortie, $participant)) {
                $this->addFlash('danger', 'Impossible d\'ajouter le participant.');
                return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
            }
        }

        $this->addFlash('success', 'Participant ajouté avec succès.');
        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
    }



    #[Route('/sortie/{id}/change-organizer', name: 'admin_change_organizer', methods: ['POST'])]
    public function changeOrganizer(Sortie $sortie, Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $participantId = $data['participantId'] ?? null;

        if (!$participantId) {
            return $this->json(['success' => false, 'message' => 'Participant non spécifié']);
        }

        $participant = $em->getRepository(Participant::class)->find($participantId);

        if (!$participant) {
            return $this->json(['success' => false, 'message' => 'Participant introuvable']);
        }

        $sortie->setOrganisateur($participant);

        if (!$sortie->getParticipants()->contains($participant)) {
            $sortie->addParticipant($participant);
        }

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Organisateur changé avec succès']);
    }

    #[Route('/sortie/{id}/remove-participant', name: 'admin_remove_participant', methods: ['POST'])]
    public function removeParticipant(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $participantId = $request->request->get('participantId');
        $motif = $request->request->get('motif');

        if (!$participantId || !$motif) {
            $this->addFlash('danger', 'Données manquantes.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        $participant = $em->getRepository(Participant::class)->find($participantId);

        if (!$participant) {
            $this->addFlash('danger', 'Participant introuvable.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        if (!$sortie->getParticipants()->contains($participant)) {
            $this->addFlash('warning', 'Ce participant n\'est pas inscrit à cette sortie.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        if ($sortie->getOrganisateur() === $participant) {
            $this->addFlash('danger', 'Impossible de retirer l\'organisateur de la sortie.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        $sortie->removeParticipant($participant);
        $em->flush();

        $this->addFlash('success', 'Participant retiré avec succès. Motif : ' . $motif);
        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
    }

}