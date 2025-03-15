<?php

namespace App\Entity;

use App\Repository\LessonValidationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonValidationRepository::class)]
class LessonValidation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'lessonValidations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Lesson::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lesson $lesson = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $validatedAt;

    public function __construct()
    {
        $this->validatedAt = new \DateTime(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User 
    { 
        return $this->user; 
    }

    public function setUser(?User $user): self 
    { 
        $this->user = $user; return $this; 
    }

    public function getLesson(): ?Lesson 
    { 
        return $this->lesson; 
    }
    public function setLesson(?Lesson $lesson): self 
    { 
        $this->lesson = $lesson; return $this; 
    }

    public function getValidatedAt(): \DateTime 
    { 
        return $this->validatedAt; 
    }
}
