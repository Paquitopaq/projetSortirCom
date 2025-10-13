<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\DeleteSortieType;
use App\Form\ImportParticipantType;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
}
