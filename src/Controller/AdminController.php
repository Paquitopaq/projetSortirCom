<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
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
}
