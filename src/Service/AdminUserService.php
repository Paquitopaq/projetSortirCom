<?php

namespace App\Service;

use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;

class AdminUserService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function toggleAdmin(Participant $participant): void
    {
        $roles = $participant->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            $roles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
        } else {
            $roles[] = 'ROLE_ADMIN';
        }

        $participant->setRoles(array_values($roles));
        $this->em->persist($participant);
        $this->em->flush();
    }
}
