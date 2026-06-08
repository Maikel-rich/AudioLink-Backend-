<?php

namespace App\Entity;

use App\Repository\BeatCatalogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: BeatCatalogRepository::class)]
#[ORM\Table(name: 'beats_catalog', schema: 'audiolink')]
class BeatCatalog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['beat:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'beats')]
    #[ORM\JoinColumn(name: "producer_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    #[Ignore]
    private ?User $producer = null;

    #[ORM\Column(length: 255)]
    #[Groups(['beat:read'])]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['beat:read'])]
    private ?int $bpm = null;

    #[ORM\Column(name: "key", length: 10, nullable: true)]
    #[Groups(['beat:read'])]
    private ?string $keySignature = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['beat:read'])]
    private ?string $genre = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['beat:read'])]
    private ?string $price = '0.00';

    #[ORM\Column(name: "cloudinary_url", type: Types::TEXT)]
    #[Groups(['beat:read'])]
    private ?string $cloudinaryUrl = null;

    #[ORM\Column(name: "tagged_audio_url", type: Types::TEXT, nullable: true)]
    #[Groups(['beat:read'])]
    private ?string $taggedAudioUrl = null;

    #[ORM\Column(name: "untagged_audio_url", type: Types::TEXT, nullable: true)]
    private ?string $untaggedAudioUrl = null;

    #[ORM\Column(name: "is_sold", type: Types::BOOLEAN, options: ["default" => false])]
    #[Groups(['beat:read'])]
    private ?bool $isSold = false;

    #[ORM\Column(name: "is_featured", type: Types::BOOLEAN, options: ["default" => false])]
    #[Groups(['beat:read'])]
    private ?bool $isFeatured = false;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE, nullable: true, options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getBpm(): ?int
    {
        return $this->bpm;
    }

    public function setBpm(?int $bpm): static
    {
        $this->bpm = $bpm;
        return $this;
    }

    public function getKeySignature(): ?string
    {
        return $this->keySignature;
    }

    public function setKeySignature(?string $keySignature): static
    {
        $this->keySignature = $keySignature;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getCloudinaryUrl(): ?string
    {
        return $this->cloudinaryUrl;
    }

    public function setCloudinaryUrl(string $cloudinaryUrl): static
    {
        $this->cloudinaryUrl = $cloudinaryUrl ?: 'https://images.unsplash.com/photo-1614613535308-eb5fbd3d2c17?q=80&w=500';
        return $this;
    }

    public function getTaggedAudioUrl(): ?string
    {
        return $this->taggedAudioUrl;
    }

    public function setTaggedAudioUrl(?string $taggedAudioUrl): static
    {
        $this->taggedAudioUrl = $taggedAudioUrl;
        return $this;
    }

    public function getUntaggedAudioUrl(): ?string
    {
        return $this->untaggedAudioUrl;
    }

    public function setUntaggedAudioUrl(?string $untaggedAudioUrl): static
    {
        $this->untaggedAudioUrl = $untaggedAudioUrl;
        return $this;
    }

    public function isSold(): ?bool
    {
        return $this->isSold;
    }

    public function setIsSold(?bool $isSold): static
    {
        $this->isSold = $isSold ?? false;
        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(?bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured ?? false;
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
