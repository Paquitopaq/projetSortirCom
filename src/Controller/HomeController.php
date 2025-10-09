<?php

namespace App\Controller;

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
        SortieService $sortieService
    ): Response
    {
        $sortieService->archiverSorties();
        $data = $sortieService->getFilteredSorties($request);

        return $this->render('home/home.html.twig', [
            'sorties' => $data['sorties'] ?? [],
            'lieux' => $data['lieux'] ?? [],
            'dateDebut' => $data['dateDebut'] ?? null,
            'dateFin' => $data['dateFin'] ?? null,
        ]);
    }
}