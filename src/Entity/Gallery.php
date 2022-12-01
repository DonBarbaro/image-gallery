<?php

namespace App\Entity;

use App\Repository\GalleryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: GalleryRepository::class)]
class Gallery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Groups('gallery')]
    #[MaxDepth(1)]
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

    /**
     * @return Collection<int, Item>
     */
    public function getGalleries(): Collection
    {
        return $this->galleries;
    }

    public function addItem(Item $item): self
    {
        if (!$this->galleries->contains($item)) {
            $this->galleries->add($item);
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->galleries->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getGallery() == $this) {
            }
        }

        return $this;
    }
}
