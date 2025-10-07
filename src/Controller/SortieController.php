<?php

namespace App\Controller;

use App\Entity\State;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Form\SortieType;
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
    public function create(Request $request, EntityManagerInterface $entityManager): Response
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
        ]);
    }

    #[Route('/sortie/{id}/publish', name: 'sortie_publier', methods: ['POST'])]
    public function publier(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        try {
            $this->sortieService->publier($sortie);
            $this->addFlash('success', 'Sortie publiée.');

        }catch (\Exception $exception){
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_sortie');
    }




}
