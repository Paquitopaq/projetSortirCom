<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\ImportParticipantType;
use App\Form\ProfilType;
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
        $nbUsers = $em->getRepository(Participant::class)->count([]);
        $nbSorties = $em->getRepository(Sortie::class)->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'nbUsers' => $nbUsers,
            'nbSorties' => $nbSorties,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(Participant::class)->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
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
    public function createProfil(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserPasswordHasherInterface $passwordHasher): Response {

        $participant = new Participant();
        $form = $this->createForm(ProfilType::class, $participant, ['is_create' => true]);
        $form -> handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                // ✅ Hasher le mot de passe sur le nouvel objet $participant
                $hashedPassword = $passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashedPassword);
            }

            $photoFile = $form->get('photoProfil')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $e->getMessage();
                }

                $participant->setPhotoProfil($newFilename);
            }
            $em->persist($participant);
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('participant/createProfil.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    #[Route('/import/{id}/sortie', name: 'admin_import')]
    public function import(Request $request,Sortie $sortie,SortieService $sortieService,EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ImportParticipantType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csvFile')->getData();

            if (($handle = fopen($csvFile->getPathname(), 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    // suppose que l’email est en première colonne, attention à ne pas changer le csv
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
}
