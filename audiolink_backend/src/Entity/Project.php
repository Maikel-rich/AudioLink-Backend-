<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects', schema: 'audiolink')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "artist_id", referencedColumnName: "id")]
    private ?User $artist = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "producer_id", referencedColumnName: "id")]
    private ?User $producer = null;

    #[ORM\Column(length: 50, options: ["default" => "active"])]
    private ?string $status = 'active';

    #[ORM\Column(name: "is_paid", options: ["default" => false])]
    private ?bool $isPaid = false;

    #[ORM\Column(name: "progress_percentage", options: ["default" => 0])]
    private ?int $progressPercentage = 0;

    #[ORM\Column(name: "current_stage_name", length: 50, nullable: true)]
    private ?string $currentStageName = null;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getArtist(): ?User
    {
        return $this->artist;
    }

    public function setArtist(?User $artist): static
    {
        $this->artist = $artist;
        return $this;
    }

    public function getProducer(): ?User
    {
        return $this->producer;
    }

    public function setProducer(?User $producer): static
    {
        $this->producer = $producer;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(?bool $isPaid): static
    {
        $this->isPaid = $isPaid;
        return $this;
    }

    public function getProgressPercentage(): ?int
    {
        return $this->progressPercentage;
    }

    public function setProgressPercentage(?int $progressPercentage): static
    {
        $this->progressPercentage = $progressPercentage;
        return $this;
    }

    public function getCurrentStageName(): ?string
    {
        return $this->currentStageName;
    }

    public function setCurrentStageName(?string $currentStageName): static
    {
        $this->currentStageName = $currentStageName;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
