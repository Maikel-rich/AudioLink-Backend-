<?php

namespace App\Entity;

use App\Repository\BeatPurchaseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BeatPurchaseRepository::class)]
#[ORM\Table(name: 'beat_purchases', schema: 'audiolink')]
class BeatPurchase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BeatCatalog::class)]
    #[ORM\JoinColumn(name: "beat_id", referencedColumnName: "id", nullable: false)]
    private ?BeatCatalog $beat = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "artist_id", referencedColumnName: "id", nullable: false)]
    private ?User $artist = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "producer_id", referencedColumnName: "id", nullable: false)]
    private ?User $producer = null;

    #[ORM\Column(name: "license_type", length: 50, options: ["default" => "standard"])]
    private ?string $licenseType = 'standard';

    #[ORM\Column(name: "price_paid", type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $pricePaid = null;

    #[ORM\Column(name: "purchase_date", type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $purchaseDate = null;

    #[ORM\Column(length: 20, options: ["default" => "completed"])]
    private ?string $status = 'completed';

    #[ORM\Column(name: "download_url", type: Types::TEXT, nullable: true)]
    private ?string $downloadUrl = null;

    public function __construct()
    {
        $this->purchaseDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBeat(): ?BeatCatalog
    {
        return $this->beat;
    }

    public function setBeat(?BeatCatalog $beat): static
    {
        $this->beat = $beat;
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

    public function getLicenseType(): ?string
    {
        return $this->licenseType;
    }

    public function setLicenseType(string $licenseType): static
    {
        $this->licenseType = $licenseType;
        return $this;
    }

    public function getPricePaid(): ?string
    {
        return $this->pricePaid;
    }

    public function setPricePaid(string $pricePaid): static
    {
        $this->pricePaid = $pricePaid;
        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeInterface $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl): static
    {
        $this->downloadUrl = $downloadUrl;
        return $this;
    }
}
