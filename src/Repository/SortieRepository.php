<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findByFilters(?string $nom, ?string $dateDebut, ?string $dateFin, $organisateur, $inscrit, $nonInscrit, $passees, $user)
    {
        $qb = $this->createQueryBuilder('s');

        $filters = [
            'nom' => $nom,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'organisateur' => $organisateur,
            'inscrit' => $inscrit,
            'nonInscrit' => $nonInscrit,
            'passees' => $passees,
        ];

        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'nom':
                    if ($value) {
                        $qb->andWhere('s.nom LIKE :nom')->setParameter('nom', '%' . $value . '%');
                    }
                    break;

                case 'dateDebut':
                    if ($value) {
                        $qb->andWhere('s.dateHeureDebut >= :dateDebut')->setParameter('dateDebut', new \DateTime($value));
                    }
                    break;

                case 'dateFin':
                    if ($value) {
                        $qb->andWhere('s.dateHeureDebut <= :dateFin')->setParameter('dateFin', new \DateTime($value));
                    }
                    break;

                case 'organisateur':
                    if ($value) {
                        $qb->andWhere('s.organisateur = :user')->setParameter('user', $user);
                    }
                    break;

                case 'inscrit':
                    if ($value) {
                        $qb->join('s.inscriptions', 'i')
                            ->andWhere('i.participant = :userInscrit')
                            ->setParameter('userInscrit', $user);
                    }
                    break;

                case 'nonInscrit':
                    if ($value) {
                        $qb->leftJoin('s.inscriptions', 'i2', 'WITH', 'i2.participant = :userNonInscrit')
                            ->andWhere('i2.id IS NULL')
                            ->setParameter('userNonInscrit', $user);
                    }
                    break;

                case 'passees':
                    if ($value) {
                        $qb->andWhere('s.dateHeureDebut < :now')->setParameter('now', new \DateTime());
                    }
                    break;
            }
        }

        $qb->orderBy('s.dateHeureDebut', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findNonArchivees(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.etat != :etat')
            ->setParameter('etat', \App\Enum\Etat::ARCHIVEE)
            ->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }


}