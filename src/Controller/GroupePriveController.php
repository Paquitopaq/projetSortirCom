<?php

namespace App\Controller;

use App\Entity\GroupePrive;
use App\Entity\Sortie;
use App\Form\GroupePriveType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-groupes-prives')]
#[IsGranted('ROLE_USER')]
class GroupePriveController extends AbstractController
{
    #[Route('', name: 'groupe_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var Participant $user */
        $user = $this->getUser();

        $groupes = $em->getRepository(GroupePrive::class)
            ->findBy(['organisateur' => $user]);

        return $this->render('participant/viewParticipant.html.twig', [
            'groupes' => $groupes,
            'participant' => $user,
        ]);
    }

    #[Route('/nouveau', name: 'nouveau_groupe', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var Participant $user */
        $user = $this->getUser();

        $groupe = new GroupePrive();
        $groupe->setOrganisateur($user);

        $form = $this->createForm(GroupePriveType::class, $groupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('membres')->getData() as $membre) {
                $groupe->getMembres()->add($membre);
            }

            $em->persist($groupe);
            $em->flush();

            $this->addFlash('success', 'Groupe privé créé avec succès.');
            return $this->redirectToRoute('groupe_index');
        }

        return $this->render('groupe_prive/create.html.twig', [
            'form' => $form,
            'groupe' => $groupe,
        ]);
    }

    #[Route('/{id}/modifier', name: 'modifier_groupe', methods: ['GET', 'POST'])]
    public function edit(GroupePrive $groupe, Request $request, EntityManagerInterface $em): Response
    {
        if ($groupe->getOrganisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce groupe.");
        }

        $form = $this->createForm(GroupePriveType::class, $groupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $em->flush();

            $this->addFlash('success', 'Groupe privé mis à jour.');
            return $this->redirectToRoute('groupe_index');
        }

        return $this->render('groupe_prive/create.html.twig', [
            'form' => $form,
            'groupe' => $groupe,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'supprimer_groupe', methods: ['POST'])]
    public function delete(GroupePrive $groupe, Request $request, EntityManagerInterface $em): Response
    {
        if ($groupe->getOrganisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce groupe.");
        }


        if ($this->isCsrfTokenValid('delete_groupe_prive_' . $groupe->getId(), $request->request->get('_token'))) {
            $em->remove($groupe);
            $em->flush();
            $this->addFlash('success', 'Groupe privé supprimé.');
        }

        return $this->redirectToRoute('groupe_index');
    }
}
