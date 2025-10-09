<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\ImportParticipantType;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use http\Env\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    #[Route('/import/{id}/sortie', name: 'admin_import')]
    public function import(Request $request,Sortie $sortie,SortieService $sortieService,EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ImportParticipantType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csvFile')->getData();

            if (($handle = fopen($csvFile->getPathname(), 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $email = $data[0]; // suppose que l’email est en première colonne
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
