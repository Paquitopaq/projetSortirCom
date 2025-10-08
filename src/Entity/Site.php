<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idSite = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'site')]
    private Collection $sorties;

    public function __construct()
    {
        $this->sorties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdSite(): ?string
    {
        return $this->idSite;
    }

    public function setIdSite(string $idSite): static
    {
        $this->idSite = $idSite;

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

    /**
     * @return Collection<int, Sortie>
     */
    public function getSorties(): Collection
    {
        return $this->sorties;
    }

    public function addVille(Sortie $ville): static
    {
        if (!$this->sorties->contains($ville)) {
            $this->sorties->add($ville);
            $ville->setSite($this);
        }

        return $this;
    }

    public function removeVille(Sortie $ville): static
    {
        if ($this->sorties->removeElement($ville)) {
            // set the owning side to null (unless already changed)
            if ($ville->getSite() === $this) {
                $ville->setSite(null);
            }
        }

        return $this;
    }
}
