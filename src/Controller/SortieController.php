<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\DeleteSortieType;
use App\Form\SortieType;
use App\Repository\LieuRepository;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SortieController extends AbstractController
{
    public function __construct(private readonly SortieService $sortieService)
    {
    }

    #[Route('/sortie', name: 'app_sortie')]
    public function index(): Response
    {
        return $this->render('sortie/index.html.twig', [
            'controller_name' => 'SortieController',
        ]);
    }

    #[Route('/sortie/create', name: 'sortie_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, LieuRepository $lieuRepository): Response
    {
        $sortie = new Sortie();
        $sortie->setIdSortie(uniqid('SRT_'));

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication = $request->request->get('action') === 'publier';
            $this->sortieService->createSortie($sortie, $publication);


            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'Sortie enregistrée en brouillon.');
            return $this->redirectToRoute('app_sortie');
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
            'lieux' => $lieuRepository->findAll(),
        ]);
    }

    #[Route('/sortie/{id}/publish', name: 'sortie_publier', methods: ['POST'])]
    public function publier(Sortie $sortie): Response
    {
        try {
            $this->sortieService->publier($sortie);
            $this->addFlash('success', 'Sortie publiée.');

        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_sortie');
    }

    #[Route('/sortie/{id}/inscrire', name: 'sortie_inscrire')]
    public function inscrire(Sortie $sortie, SortieService $sortieService, EntityManagerInterface $em): Response
    {
        $participant = $em->getRepository(Participant::class)->find($this->getUser()->getId());

        if ($sortieService->inscrireParticipant($sortie, $participant)) {
            $this->addFlash('success', 'Inscription réussie !');
        } else {
            $this->addFlash('danger', 'Impossible de vous inscrire.');
        }

        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
    }

    #[Route('/sortie/{id}/detail', name: 'sortie_detail')]
    public function getSortie(?Sortie $sortie): Response
    {
        if (!$sortie) {
            throw $this->createNotFoundException("Cette sortie n'existe pas.");
        }

        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sortie/{id}/delete', name: 'sortie_delete', methods: ['GET', 'POST'])]
    public function delete(
        Sortie $sortie,
        Request $request,
        SortieService $sortieService,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(DeleteSortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $sortieService->annulerSortie($sortie, $this->getUser());

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                return $this->redirectToRoute('home');
            } else {
                $this->addFlash('danger', $result['message']);
            }
        }

        return $this->render('sortie/delete.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }



}
