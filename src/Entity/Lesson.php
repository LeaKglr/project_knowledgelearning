<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use DateTime;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)] 
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)] 
    private ?string $video = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private ?string $price = '0.00';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable(); 
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string 
    { 
        return $this->description; 
    }
    
    public function setDescription(?string $description): self 
    { 
        $this->description = $description; 
        return $this; 
    }

    public function getVideo(): ?string 
    { 
        return $this->video; 
    }

    public function setVideo(?string $video): self 
    { 
        $this->video = $video; 
        return $this; 
    }

    public function getCourse(): ?Course
    { 
        return $this->course; 
    }
    
    public function setCourse(?Course $course): self 
    { 
        $this->course = $course; 
        return $this; 
    }

    public function getPrice(): float
    {
        return $this->price ?? 0;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable 
    { 
        return $this->createdAt; 
    }

    public function getUpdatedAt(): ?\DateTime 
    { 
        return $this->updatedAt; 
    }
}
