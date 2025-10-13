<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ville', name: 'ville')]
final class VilleController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/create', name: '_ajouter')]
    public function ajouter(Request $request, EntityManagerInterface $em): Response
    {
        $ville = new Ville();
        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ville);
            $em->flush();

            $this->addFlash('success', 'La ville a bien été ajoutée !');
            return $this->redirectToRoute('ville_liste');
        }

        return $this->render('ville/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/liste', name: '_liste')]
    public function liste(VilleRepository $repo): Response
    {
        return $this->render('ville/liste.html.twig', [
            'villes' => $repo->findAll(),
        ]);
    }
}