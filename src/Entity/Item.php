<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Groups('item')]
    #[ORM\Column(length: 255)]
    private string $path;

    #[Groups('item')]
    #[ORM\Column(length: 255)]
    private string $name;

    #[Groups('gallery')]
    #[ORM\ManyToOne(inversedBy: 'galleries')]
    #[MaxDepth(1)]
    private string $gallery;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getGallery(): string
    {
        return $this->gallery;
    }

    public function setGallery(string $gallery): self
    {
        $this->gallery = $gallery;

        return $this;
    }
}
