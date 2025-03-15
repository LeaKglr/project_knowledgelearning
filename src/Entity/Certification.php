<?php

namespace App\Entity;

use App\Repository\CertificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CertificationRepository::class)]
class Certification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'certifications')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Theme::class)]
    private Theme $theme;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $obtainedAt;

    public function __construct(User $user, Theme $theme)
    {
        $this->user = $user;
        $this->theme = $theme;
        $this->obtainedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    public function setTheme(Theme $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function getObtainedAt(): \DateTime
    {
        return $this->obtainedAt;
    }

    public function setObtainedAt(\DateTime $obtainedAt): self
    {
        $this->obtainedAt = $obtainedAt;
        return $this;
    }
}
