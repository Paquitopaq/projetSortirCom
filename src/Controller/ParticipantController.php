<?php

namespace App\Controller;

use App\Entity\GroupePrive;
use App\Entity\Participant;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profil')]
final class ParticipantController extends AbstractController
{
    #[Route('/update', name: 'app_profil_update')]
    public function updateProfil(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $participant = $this->getUser();

        if (!$participant instanceof Participant) {
            $this->addFlash('error', 'Utilisateur non connecté.');
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfilType::class, $participant, ['is_create' => true]);
        $form -> handleRequest($request);

        $avatarDirectory = $this->getParameter('kernel.project_dir') . '/public/assets/avatars';
        $avatars = array_diff(scandir($avatarDirectory), ['..', '.']);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedAvatar = $request->request->get('selected_avatar');
            $photoFile = $form->get('photoProfil')->getData();

            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                $participant->setPassword($userPasswordHasher->hashPassword($participant, $plainPassword));
            }


            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move($this->getParameter('photo_directory'), $newFilename);
                    $participant->setPhotoProfil($newFilename);
                    $participant->setPhotoSource('image');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de la photo.');
                }
            } elseif ($selectedAvatar) {
                $participant->setPhotoProfil($selectedAvatar);
                $participant->setPhotoSource('avatar');
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_home');
        }


        return $this->render('participant/updateProfil.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
            'avatars' => $avatars,
        ]);
    }

    #[Route('/{id}', name: 'app_profil_participant', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function participantProfil(int $id, ParticipantRepository $participantRepository, EntityManagerInterface $em): Response
    {
        // Récupérer le participant avec ses sorties et sorties organisées
        $participant = $participantRepository->findWithSorties($id);
        $groupes = $em->getRepository(GroupePrive::class)
            ->findBy(['organisateur' => $participant]);
        if (!$participant) {
            throw $this->createNotFoundException('Participant introuvable.');
        }

        $participant->getSorties()->count();
        $participant->getSortiesOrganisees()->count();

        return $this->render('participant/viewParticipant.html.twig', [
            'participant' => $participant,
            'groupes' => $groupes,
        ]);
    }



}
