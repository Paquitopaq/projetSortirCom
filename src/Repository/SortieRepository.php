<?php

namespace App\Repository;

use App\Entity\Participant;
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

//    public function findByFilters(?string $nom, ?string $dateDebut, ?string $dateFin, $organisateur, $inscrit, $nonInscrit, $passees, $user)
//    {
//        $qb = $this->createQueryBuilder('s');
//
//        $qb = $this->createQueryBuilder('s');
//
//        // Exclure les sorties archivées
//        $qb->andWhere('s.etat != :etatArchivee')
//            ->setParameter('etatArchivee', \App\Enum\Etat::ARCHIVEE);
//
//        $filters = [
//            'nom' => $nom,
//            'dateDebut' => $dateDebut,
//            'dateFin' => $dateFin,
//            'organisateur' => $organisateur,
//            'inscrit' => $inscrit,
//            'nonInscrit' => $nonInscrit,
//            'passees' => $passees,
//        ];
//
//        foreach ($filters as $key => $value) {
//            switch ($key) {
//                case 'nom':
//                    if ($value) {
//                        $qb->andWhere('s.nom LIKE :nom')->setParameter('nom', '%' . $value . '%');
//                    }
//                    break;
//
//                case 'dateDebut':
//                    if ($value) {
//                        $qb->andWhere('s.dateHeureDebut >= :dateDebut')->setParameter('dateDebut', new \DateTime($value));
//                    }
//                    break;
//
//                case 'dateFin':
//                    if ($value) {
//                        $qb->andWhere('s.dateHeureDebut <= :dateFin')->setParameter('dateFin', new \DateTime($value));
//                    }
//                    break;
//
//                case 'organisateur':
//                    if ($value) {
//                        $qb->andWhere('s.organisateur = :user')->setParameter('user', $user);
//                    }
//                    break;
//
//                case 'inscrit':
//                    if ($value) {
//                        $qb->join('s.inscriptions', 'i')
//                            ->andWhere('i.participant = :userInscrit')
//                            ->setParameter('userInscrit', $user);
//                    }
//                    break;
//
//                case 'nonInscrit':
//                    if ($value) {
//                        $qb->leftJoin('s.inscriptions', 'i2', 'WITH', 'i2.participant = :userNonInscrit')
//                            ->andWhere('i2.id IS NULL')
//                            ->setParameter('userNonInscrit', $user);
//                    }
//                    break;
//
//                case 'passees':
//                    if ($value) {
//                        $qb->andWhere('s.dateHeureDebut < :now')->setParameter('now', new \DateTime());
//                    }
//                    break;
//            }
//        }
//
//        $qb->orderBy('s.dateHeureDebut', 'ASC');
//
//        return $qb->getQuery()->getResult();
//    }

    public function findByFilters(
        ?string $nomRecherche,
        ?string $dateDebut,
        ?string $dateFin,
        ?bool $organisateur,
        ?bool $inscrit,
        ?bool $nonInscrit,
        ?bool $passees,
        Participant $user
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.participants', 'p')
            ->addSelect('p')
            ->where('s.etat != :etatArchivee')
            ->setParameter('etatArchivee', \App\Enum\Etat::ARCHIVEE);

        if ($nomRecherche) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $nomRecherche . '%');
        }

        if ($dateDebut) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($dateDebut));
        }

        if ($dateFin) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', new \DateTime($dateFin));
        }

        if ($organisateur) {
            $qb->andWhere('s.organisateur = :user')
                ->setParameter('user', $user);
        }

        if ($inscrit) {
            $qb->andWhere(':user MEMBER OF s.participants')
                ->setParameter('user', $user);
        }

        if ($nonInscrit) {
            $qb->andWhere(':user NOT MEMBER OF s.participants')
                ->setParameter('user', $user);
        }

        if ($passees) {
            $qb->andWhere('s.dateHeureDebut < :now')
                ->setParameter('now', new \DateTime());
        }

        return $qb->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function archiverSortiesAnciennes(): int
    {
        $qb = $this->createQueryBuilder('s')
            ->update()
            ->set('s.etat', ':etat')
            ->where('s.dateHeureDebut < :limit')
            ->setParameter('etat', \App\Enum\Etat::ARCHIVEE)
            ->setParameter('limit', (new \DateTime())->modify('-1 month'));

        return $qb->getQuery()->execute(); // retourne le nombre de lignes mises à jour
    }


}