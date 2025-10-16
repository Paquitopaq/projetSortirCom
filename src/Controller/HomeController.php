<?php

namespace App\Controller;

use App\Enum\Etat;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use App\Service\SortieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        SortieService $sortieService,
        SortieRepository $sortieRepository,
        ParticipantRepository $participantRepository
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $sortieService->archiverSorties();
        $sortieService->clotureSorties();

        $data = $sortieService->getFilteredSorties($request);


        $sortiesDisponibles = $sortieRepository->countByEtat(Etat::OPEN);

        $participantsActifs = $participantRepository->countActifs();

        $organisateurs = $participantRepository->countOrganisateurs();

        // (Optionnel) Tu peux aussi calculer :
        // $sortiesTotal = $sortieRepository->count([]);
        // $villesActives = $sortieRepository->countDistinctVilles();

        return $this->render('home/home.html.twig', [
            'sorties' => $data['sorties'] ?? [],
            'lieux' => $data['lieux'] ?? [],
            'dateDebut' => $data['dateDebut'] ?? null,
            'dateFin' => $data['dateFin'] ?? null,
            'participant' => $this->getUser(),

            'sortiesDisponibles' => $sortiesDisponibles,
            'participantsActifs' => $participantsActifs,
            'organisateurs' => $organisateurs,
        ]);
    }
}
