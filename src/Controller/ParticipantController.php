<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ProfilType;
use App\Form\UpdateProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profil')]
final class ParticipantController extends AbstractController
{
    #[Route('/update', name: 'app_profil_update')]
    public function updateProfil(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $participant = $this->getUser();
        $form = $this->createForm(ProfilType::class, $participant, ['is_create' => false]);
        $form -> handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_home');
        }


        return $this->render('participant/updateProfil.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    #[Route('/{id}', name: 'app_profil_participant', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function participantProfil(Participant $participant): Response
    {
        return $this->render('participant/viewParticipant.html.twig', [
            'participant' => $participant,
        ]);
    }

}
