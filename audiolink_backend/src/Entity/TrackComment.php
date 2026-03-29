<?php

namespace App\Entity;

use App\Repository\TrackCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackCommentRepository::class)]
#[ORM\Table(name: 'track_comments', schema: 'audiolink')]
class TrackComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Track::class)]
    #[ORM\JoinColumn(name: "track_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?Track $track = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id")]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(name: "timestamp_seconds", type: Types::FLOAT)]
    private ?float $timestampSeconds = null;

    #[ORM\Column(name: "is_resolved", options: ["default" => false])]
    private ?bool $isResolved = false;

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

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): static
    {
        $this->track = $track;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getTimestampSeconds(): ?float
    {
        return $this->timestampSeconds;
    }

    public function setTimestampSeconds(float $timestampSeconds): static
    {
        $this->timestampSeconds = $timestampSeconds;
        return $this;
    }

    public function isResolved(): ?bool
    {
        return $this->isResolved;
    }

    public function setIsResolved(bool $isResolved): static
    {
        $this->isResolved = $isResolved;
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
