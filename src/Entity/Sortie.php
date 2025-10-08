<?php

namespace App\Entity;

use App\Enum\Etat;
use App\Enum\State;
use App\Repository\SortieRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idSortie = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThan("today", message: "La date de début doit être supérieur à aujourd'hui")]
    private ?\DateTime $dateHeureDebut = null;

    #[ORM\Column]
    private ?int $duree = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "La date limite d'inscription est obligatoire")]
    private ?\DateTime $dateLimiteInscription = null;

    #[ORM\Column]
    private ?int $nbInscriptionMax = null;

    #[ORM\Column(length: 255)]
    private ?string $infoSortie = null;

    #[ORM\Column(enumType: Etat::class)]
    private ?Etat $etat = null;


    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'sorties')]
    #[ORM\JoinTable(name: 'sortie_participant')]
    private Collection $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdSortie(): ?string
    {
        return $this->idSortie;
    }

    public function setIdSortie(string $idSortie): static
    {
        $this->idSortie = $idSortie;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateHeureDebut(): ?\DateTime
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(?\DateTime $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateLimiteInscription(): ?\DateTime
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(\DateTime $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;

        return $this;
    }

    public function getNbInscriptionMax(): ?int
    {
        return $this->nbInscriptionMax;
    }

    public function setNbInscriptionMax(int $nbInscriptionMax): static
    {
        $this->nbInscriptionMax = $nbInscriptionMax;

        return $this;
    }

    public function getInfoSortie(): ?string
    {
        return $this->infoSortie;
    }

    public function setInfoSortie(string $infoSortie): static
    {
        $this->infoSortie = $infoSortie;

        return $this;
    }

    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(?Etat $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function setParticipants(Collection $participants): void
    {
        $this->participants = $participants;
    }




    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->dateLimiteInscription && $this->dateHeureDebut) {
            if ($this->dateLimiteInscription > $this->dateHeureDebut) {
                $context->buildViolation("La date limite d'inscription doit être avant la date de début")
                    ->atPath('dateLimiteInscription')
                    ->addViolation();
            }
        }
    }
    public function updateEtat(): void
    {
        $now = new \DateTime();

        if ($this->etat->getIdEtat() === 'CREATION') {
            // Rien à faire, attend publication
            return;
        }

        if ($this->etat->getIdEtat() === 'OUVERTE') {
            if ($this->dateLimiteInscription < $now || $this->getNbInscriptionMax() <= $this->getNbInscrits()) {
                $this->etat->setIdEtat('CLOTUREE');
            }
        }

        if ($this->etat->getIdEtat() === 'CLOTUREE') {
            if ($this->dateHeureDebut <= $now) {
                $this->etat->setIdEtat('EN_COURS');
            }
        }

        if ($this->etat->getIdEtat() === 'EN_COURS') {
            $fin = clone $this->dateHeureDebut;
            $fin->modify("+{$this->duree} minutes");

            if ($fin <= $now) {
                $this->etat->setIdEtat('TERMINEE');
            }
        }

        // Rajouter les autres changements quand on fera les features annulé ou archiver
    }


}
