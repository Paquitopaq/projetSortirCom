<?php

namespace App\Entity;

use App\Enum\Etat;
use App\Repository\SortieRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: "Le nom de la sortie est obligatoire.")]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThan("today", message: "La date de début doit être supérieur à aujourd'hui")]
    private ?DateTimeImmutable $dateHeureDebut = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotNull(message: "La durée est obligatoire.")]
    #[Assert\Positive(message: "La durée doit être un nombre positif.")]
    private ?int $duree = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotNull(message: "La date limite d'inscription est obligatoire")]
    #[Assert\GreaterThan("today", message: "La date limite d'inscription doit être supérieur à aujourd'hui")]
    private ?DateTimeImmutable $dateLimiteInscription = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotNull(message: "Renseigner le nombre d'inscription maximum.")]
    private ?int $nbInscriptionMax = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $infoSortie = null;

    #[ORM\Column(enumType: Etat::class)]
    private ?Etat $etat = null;

    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'sorties')]
    #[ORM\JoinTable(name: 'sortie_participant')]
    private Collection $participants;

    #[ORM\ManyToOne(targetEntity: Participant::class, inversedBy: 'sortiesOrganisees')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Participant $organisateur = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    private ?Lieu $lieu = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifAnnulation = null;

    /**
     * @var Collection<int, GroupePrive>
     */
    #[ORM\ManyToOne(targetEntity: GroupePrive::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?GroupePrive $groupePrive = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateHeureDebut(): ?DateTimeImmutable
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(?DateTimeImmutable $dateHeureDebut): static
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

    public function getDateLimiteInscription(): ?DateTimeImmutable
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(DateTimeImmutable $dateLimiteInscription): static
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

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): static
    {
        $this->organisateur = $organisateur;
        return $this;
    }

    // Méthode helper pour vérifier si un participant est l'organisateur
    public function isOrganisateur(Participant $participant): bool
    {
        return $this->organisateur === $participant;
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
        $now = new \DateTimeImmutable();

        // Si la sortie est en brouillon, on ne change pas son état
        if ($this->etat === Etat::CREATED) {
            return;
        }

        // Si la sortie est ouverte
        if ($this->etat === Etat::OPEN) {
            if ($this->dateLimiteInscription < $now || $this->getNbInscrits() >= $this->nbInscriptionMax) {
                $this->etat = Etat::CLOSED;
            }
        }

        // Si la sortie est clôturée
        if ($this->etat === Etat::CLOSED) {
            if ($this->dateHeureDebut <= $now) {
                $this->etat = Etat::IN_PROGRESS;
            }
        }

        // Si la sortie est en cours
        if ($this->etat === Etat::IN_PROGRESS) {
            $fin = $this->dateHeureDebut->modify("+{$this->duree} minutes");
            if ($fin <= $now) {
                $this->etat = Etat::PASSED;
            }
        }

        if($this->etat === Etat::CANCELLED){
            return;
        }
    }


    public function getNbInscrits(): int
    {
        return $this->participants->count();
    }

    public function addParticipant(Participant $participant): void
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }
    }

    public function removeParticipant(Participant $participant): void
    {
        $this->participants->removeElement($participant);
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getMotifAnnulation(): ?string
    {
        return $this->motifAnnulation;
    }

    public function setMotifAnnulation(?string $motifAnnulation): static
    {
        $this->motifAnnulation = $motifAnnulation;
        return $this;
    }

    public function getGroupePrive(): ?GroupePrive
    {
        return $this->groupePrive;
    }

    public function setGroupePrive(?GroupePrive $groupePrive): static
    {
        $this->groupePrive = $groupePrive;
        return $this;
    }

    public function isUserAllowedToRegister(Participant $user): bool
    {
        if ($this->groupePrive === null) {
            return true;
        }

        return $this->groupePrive->getMembres()->contains($user);
    }


}
