<?php

namespace App\Entity;

use App\Repository\GalleryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GalleryRepository::class)]
class Gallery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column]
    private bool $additionalProperties;

    #[ORM\OneToMany(mappedBy: 'gallery', targetEntity: Item::class)]
    private Collection $galleries;

    public function __construct()
    {
        $this->galleries = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function isAdditionalProperties(): bool
    {
        return $this->additionalProperties;
    }

    public function setAdditionalProperties(bool $additionalProperties): self
    {
        $this->additionalProperties = $additionalProperties;

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getGalleries(): Collection
    {
        return $this->galleries;
    }

    public function addGallery(Item $gallery): self
    {
        if (!$this->galleries->contains($gallery)) {
            $this->galleries->add($gallery);
            $gallery->setGallery($this);
        }

        return $this;
    }

    public function removeGallery(Item $gallery): self
    {
        if ($this->galleries->removeElement($gallery)) {
            // set the owning side to null (unless already changed)
            if ($gallery->getGallery() === $this) {
                $gallery->setGallery(null);
            }
        }

        return $this;
    }
}
