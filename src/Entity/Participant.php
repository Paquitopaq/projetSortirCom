<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class Participant implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: 'Veuillez renseigner un email valide')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type : 'json')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    #[Assert\NotBlank(message: "Le pseudo est obligatoire.")]
    private ?string $pseudo = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 12, nullable: true)]
    #[Assert\Regex(pattern: '/^(?=(?:.*\d){10,})(?:0\d(?:[ .-]?\d{2}){4}|\+?\d{1,3}(?:[ .-]?\d{1,4}){2,5})$/', message: 'le telephone n\'est pas valide')]
    private ?string $telephone = null;

    #[ORM\Column]
    private ?bool $actif = true;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $photoProfil = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $photoSource = null;

    #[ORM\ManyToMany(targetEntity: Sortie::class, mappedBy: 'participants')]
    private Collection $sorties;

    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'organisateur')]
    private Collection $sortiesOrganisees;

    /**
     * @var Collection<int, GroupePrive>
     */
    #[ORM\OneToMany(targetEntity: GroupePrive::class, mappedBy: 'organisateur')]
    private Collection $groupePrives;

    /**
     * @var Collection<int, GroupePrive>
     */
    #[ORM\ManyToMany(targetEntity: GroupePrive::class, mappedBy: 'membres')]
    private Collection $membreGroupe;

    public function __construct()
    {
        $this->sorties = new ArrayCollection();
        $this->sortiesOrganisees = new ArrayCollection();
        $this->groupePrives = new ArrayCollection();
        $this->membreGroupe = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): void
    {
        $this->pseudo = $pseudo;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): void
    {
        $this->nom = $nom;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(?bool $actif): void
    {
        $this->actif = $actif;
    }

    public function getPhotoSource(): ?string
    {
        return $this->photoSource;
    }

    public function setPhotoSource(?string $photoSource): void
    {
        $this->photoSource = $photoSource;
    }

    public function getAvatarPath(): string
    {
        if (!$this->photoProfil) {
            return 'assets/avatars/cchat.png';
        }

        return $this->photoSource === 'avatar'
            ? 'assets/avatars/' . $this->photoProfil
            : 'assets/images/' . $this->photoProfil;
    }

    public function getSorties(): Collection
    {
        return $this->sorties;
    }

    public function getSortiesOrganisees(): Collection
    {
        return $this->sortiesOrganisees;
    }

    public function addSortie(Sortie $sortie): self
    {
        if (!$this->sorties->contains($sortie)) {
            $this->sorties->add($sortie);
            $sortie->addParticipant($this); // synchronisation côté Sortie
        }

        return $this;
    }

    public function removeSortie(Sortie $sortie): self
    {
        if ($this->sorties->removeElement($sortie)) {
            $sortie->removeParticipant($this); // synchronisation côté Sortie
        }

        return $this;
    }

    #[Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photoProfil;
    }

    public function setPhotoProfil(?string $photoProfil): static
    {
        $this->photoProfil = $photoProfil;

        return $this;
    }

    /**
     * @return Collection<int, GroupePrive>
     */
    public function getGroupePrives(): Collection
    {
        return $this->groupePrives;
    }

    public function addGroupePrife(GroupePrive $groupePrife): static
    {
        if (!$this->groupePrives->contains($groupePrife)) {
            $this->groupePrives->add($groupePrife);
            $groupePrife->setOrganisateur($this);
        }

        return $this;
    }

    public function removeGroupePrife(GroupePrive $groupePrife): static
    {
        if ($this->groupePrives->removeElement($groupePrife)) {
            // set the owning side to null (unless already changed)
            if ($groupePrife->getOrganisateur() === $this) {
                $groupePrife->setOrganisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GroupePrive>
     */
    public function getMembreGroupe(): Collection
    {
        return $this->membreGroupe;
    }

    public function addMembreGroupe(GroupePrive $membreGroupe): static
    {
        if (!$this->membreGroupe->contains($membreGroupe)) {
            $this->membreGroupe->add($membreGroupe);
            $membreGroupe->addMembre($this);
        }

        return $this;
    }

    public function removeMembreGroupe(GroupePrive $membreGroupe): static
    {
        if ($this->membreGroupe->removeElement($membreGroupe)) {
            $membreGroupe->removeMembre($this);
        }

        return $this;
    }
}
