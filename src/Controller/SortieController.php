<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Form\DeleteSortieType;
use App\Form\ImportParticipantType;
use App\Form\SortieType;
use App\Repository\LieuRepository;
use App\Service\ImportService;
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
    public function index(Request $request): Response
    {
        $data = $this->sortieService->getFilteredSorties($request);

        return $this->render('sortie/index.html.twig', [
            'sorties' => $data['sorties'],
        ]);
    }

    #[Route('/sortie/create', name: 'sortie_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, LieuRepository $lieuRepository): Response
    {
        $sortie = new Sortie();

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $nouveauLieu = $form->get('nouveauLieu')->getData();

            if ($nouveauLieu && $nouveauLieu->getNom()) {
                $entityManager->persist($nouveauLieu);
                $sortie->setLieu($nouveauLieu);
            }

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
    public function inscription(Sortie $sortie, SortieService $sortieService, EntityManagerInterface $em): Response
    {
        $participant = $em->getRepository(Participant::class)->find($this->getUser()->getId());

        if ($sortieService->inscrireParticipant($sortie, $participant)) {
            $this->addFlash('success', 'Inscription à la sortie réussie !');
        }else if ($sortieService->removeParticipant($sortie, $participant)) {
            $this->addFlash('success', 'vous n\'êtes plus inscrit à cette sortie.');
        }else {
            $this->addFlash('danger', 'Les inscription et désinscription ne sont plus possible pour cette sortie.');
        }

        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
    }


    #[Route('/sortie/{id}/detail', name: 'sortie_detail')]
    public function detailsortie(
        Request                $request,
        ?Sortie                $sortie,
        SortieService          $sortieService,
        ImportService          $importService,
        EntityManagerInterface $em
    ): Response {
        if (!$sortie) {
            $this->addFlash('danger', "Cette sortie n'existe pas.");
            return $this->redirectToRoute('app_home');
        }

        if ($sortie->getEtat() === Etat::ARCHIVEE) {
            $this->addFlash('danger', "Cette sortie n'est plus consultable.");
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ImportParticipantType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->getUser()?->getAdministrateur()) {
            $csvFile = $form->get('csv_file')->getData();

            $messages = $importService->importerEtInscrire($csvFile, $sortie);

            foreach ($messages as $msg) {
                $this->addFlash($msg['type'], $msg['text']);
            }

            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }



    #[Route('/sortie/{id}/delete', name: 'sortie_delete', methods: ['GET', 'POST'])]
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
