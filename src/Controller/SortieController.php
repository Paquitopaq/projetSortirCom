<?php

namespace App\Controller;

use App\Entity\GroupePrive;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Form\DeleteSortieType;
use App\Form\GroupePriveType;
use App\Form\ImportParticipantType;
use App\Form\SortieType;
use App\Repository\GroupePriveRepository;
use App\Repository\LieuRepository;
use App\Service\ImportService;
use App\Service\SortieService;
use App\Utils\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    public function index(Request $request,EntityManagerInterface $entityManager): Response
    {
        $data = $this->sortieService->getFilteredSorties($request);
        $sorties = $data['sorties'];

        foreach ($sorties as $sortie) {
            $sortie->updateEtat();
        }

        $entityManager->flush();

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/sortie/create', name: 'sortie_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, LieuRepository $lieuRepository, GroupePriveRepository $groupePriveRepository, FileManager $fileManager): Response
    {
        $sortie = new Sortie();

        $form = $this->createForm(SortieType::class, $sortie,['organisateur' => $this->getUser(),]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Nouveau lieu
            $nouveauLieu = $form->get('nouveauLieu')->getData();
            if ($nouveauLieu && $nouveauLieu->getNom()) {
                $entityManager->persist($nouveauLieu);
                $sortie->setLieu($nouveauLieu);
            }
            $groupePrive = $form->get('groupePrive')->getData();
            if ($groupePrive) {
                $sortie->setGroupePrive($groupePrive);
            }

            $publication = $request->request->get('action') === 'publier';

            // Upload photo
            $photoFile = $form->get('photoSortie')->getData();
            if ($photoFile instanceof UploadedFile) {
                $newFilename = $fileManager->upload(
                    $photoFile,
                    $this->getParameter('sorties_photos_directory'),
                    $sortie->getNom() // facultatif, juste pour base du nom
                );
                if ($newFilename) {
                    $sortie->setPhotoSortie($newFilename);
                } else {
                    $this->addFlash('warning', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $this->sortieService->createSortie($sortie, $form, $publication);

            // Upload photo
            $photoFile = $form->get('photoSortie')->getData();
            if ($photoFile instanceof UploadedFile) {
                $newFilename = $fileManager->upload(
                    $photoFile,
                    $this->getParameter('sorties_photos_directory'),
                    $sortie->getNom() // facultatif, juste pour base du nom
                );
                if ($newFilename) {
                    $sortie->setPhotoSortie($newFilename);
                } else {
                    $this->addFlash('warning', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $this->sortieService->createSortie($sortie, $form, $publication);
            $entityManager->persist($sortie);
            $entityManager->flush();

            $message = $publication ? 'Sortie publiée avec succès.' : 'Sortie enregistrée en brouillon.';
            $this->addFlash('success', $message);
            return $this->redirectToRoute('app_sortie');
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
            'lieux' => $lieuRepository->findAll(),
            'groupes' => $groupePriveRepository->findAll(),
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

    #[Route('/sortie/{id}/valider', name: 'sortie_valider', methods: ['POST'])]
    public function valider(Sortie $sortie): Response
    {
        $user = $this->getUser();
        $result = $this->sortieService->validerSortie($sortie, $user);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
    }

    #[Route('/sortie/{id}/inscrire', name: 'sortie_inscrire')]
    public function inscription(Sortie $sortie, SortieService $sortieService, EntityManagerInterface $em): Response
    {
        $participant = $em->getRepository(Participant::class)->find($this->getUser()->getId());
        if(!$sortie->isUserAllowedToRegister($participant)) {
            $this->addFlash('danger', "Vous ne faites pas partie du groupe privé associé à cette sortie.");
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }
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
        $sortie->updateEtat();

        if ($form->isSubmitted() && $form->isValid() && $this->isGranted('ROLE_ADMIN')) {
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
        // Vérification des droits
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour annuler une sortie.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier que l'utilisateur est soit l'organisateur soit un admin
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isOrganisateur = $sortie->getOrganisateur() === $user;

        if (!$isOrganisateur && !$isAdmin) {
            $this->addFlash('danger', 'Vous n\'avez pas le droit d\'annuler cette sortie.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        // Vérifier que la sortie n'est pas déjà annulée
        if ($sortie->getEtat() === Etat::CANCELLED) {
            $this->addFlash('warning', 'Cette sortie est déjà annulée.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        $form = $this->createForm(DeleteSortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motif = $form->get('motifAnnulation')->getData();

            if (!$motif || trim($motif) === '') {
                $this->addFlash('danger', 'Le motif d\'annulation est obligatoire.');
                return $this->render('sortie/delete.html.twig', [
                    'sortie' => $sortie,
                    'form' => $form->createView(),
                ]);
            }

            $result = $sortieService->deleteSortie($sortie, $user, $motif);

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                return $this->redirectToRoute('app_sortie');
            } else {
                $this->addFlash('danger', $result['message']);
            }
        }

        return $this->render('sortie/delete.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sortie/{id}/edit', name: 'sortie_edit')]
    public function edit(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $entityManager,
        LieuRepository $lieuRepository,
        FileManager $fileManager
    ): Response {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Vérifications droits et état...

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        $publication = $request->request->get('action') === 'publier';

        if ($form->isSubmitted() && $form->isValid()) {
            // Nouveau lieu
            $nouveauLieu = $form->get('nouveauLieu')->getData();
            if ($nouveauLieu && $nouveauLieu->getNom()) {
                $entityManager->persist($nouveauLieu);
                $sortie->setLieu($nouveauLieu);
            }

            // Upload photo
            $photoFile = $form->get('photoSortie')->getData();
            if ($photoFile instanceof UploadedFile) {
                $newFilename = $fileManager->upload(
                    $photoFile,
                    $this->getParameter('sorties_photos_directory'),
                    $sortie->getNom(),        // facultatif, juste pour base du nom
                    $sortie->getPhotoSortie() // ancien nom pour suppression
                );
                if ($newFilename) {
                    $sortie->setPhotoSortie($newFilename);
                } else {
                    $this->addFlash('warning', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $this->sortieService->createSortie($sortie, $form,$publication);
            $entityManager->flush();

            $message = $publication ? 'Sortie publiée avec succès.' : 'Sortie enregistrée en brouillon.';
            $this->addFlash('success', $message);
            return $this->redirectToRoute('app_sortie');
        }

        return $this->render('sortie/edit.html.twig', [
            'form' => $form->createView(),
            'lieux' => $lieuRepository->findAll(),
            'sortie' => $sortie,
        ]);
    }
}
