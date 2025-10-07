<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\UpdateProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParticipantController extends AbstractController
{
    #[Route('/participant', name: 'app_participant')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $participant = $this->getUser();
        $form = $this->createForm(UpdateProfilType::class, $participant);
        $form -> handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            return $this->redirectToRoute('app_home');
        }


        return $this->render('participant/updateProfil.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
