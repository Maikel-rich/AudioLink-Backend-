<?php

namespace App\Entity;

use App\Repository\TrackRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TrackRepository::class)]
#[ORM\Table(name: 'tracks', schema: 'audiolink')]
class Track
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['track:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: "project_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?Project $project = null;

    #[ORM\Column(name: "cloudinary_url", type: Types::TEXT)]
    #[Groups(['track:read', 'track:write'])]
    private ?string $cloudinaryUrl = null;

    #[ORM\Column(name: "version_name", length: 100, nullable: true)]
    #[Groups(['track:read', 'track:write'])]
    private ?string $versionName = null;

    #[ORM\Column(name: "is_final", options: ["default" => false])]
    #[Groups(['track:read'])]
    private ?bool $isFinal = false;

    #[ORM\Column(length: 20, options: ["default" => "pendiente"])]
    #[Groups(['track:read'])]
    private ?string $status = 'pendiente';

    #[ORM\Column(name: "file_size_mb", type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['track:read'])]
    private ?string $fileSizeMb = null;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Groups(['track:read'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getCloudinaryUrl(): ?string
    {
        return $this->cloudinaryUrl;
    }

    public function setCloudinaryUrl(string $cloudinaryUrl): static
    {
        $this->cloudinaryUrl = $cloudinaryUrl;
        return $this;
    }

    public function getVersionName(): ?string
    {
        return $this->versionName;
    }

    public function setVersionName(?string $versionName): static
    {
        $this->versionName = $versionName;
        return $this;
    }

    public function isFinal(): ?bool
    {
        return $this->isFinal;
    }

    public function setIsFinal(?bool $isFinal): static
    {
        $this->isFinal = $isFinal;
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

    public function getFileSizeMb(): ?string
    {
        return $this->fileSizeMb;
    }

    public function setFileSizeMb(?string $fileSizeMb): static
    {
        $this->fileSizeMb = $fileSizeMb;
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
