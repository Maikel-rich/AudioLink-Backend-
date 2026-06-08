<?php

namespace App\Entity;

use App\Repository\ServiceRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRequestRepository::class)]
#[ORM\Table(name: 'service_requests', schema: 'audiolink')]
class ServiceRequest
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_NOT_PAID = 0;
    public const PAYMENT_DEPOSIT_PAID = 1;
    public const PAYMENT_FULL_PAID = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "artist_id", referencedColumnName: "id", nullable: false)]
    private ?User $artist = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "producer_id", referencedColumnName: "id", nullable: false)]
    private ?User $producer = null;

    #[ORM\ManyToOne(targetEntity: ProducerService::class)]
    #[ORM\JoinColumn(name: "service_id", referencedColumnName: "id", nullable: false)]
    private ?ProducerService $service = null;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: "project_details", type: Types::TEXT, nullable: true)]
    private ?string $projectDetails = null;

    #[ORM\Column(name: "payment_intent_id", length: 255, nullable: true)]
    private ?string $paymentIntentId = null;

    #[ORM\Column(name: "amount", type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = '0.00';

    #[ORM\Column(name: "is_paid", type: Types::INTEGER, options: ["default" => 0])]
    private ?int $isPaid = self::PAYMENT_NOT_PAID;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: "updated_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getService(): ?ProducerService
    {
        return $this->service;
    }

    public function setService(?ProducerService $service): static
    {
        $this->service = $service;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getProjectDetails(): ?string
    {
        return $this->projectDetails;
    }

    public function setProjectDetails(?string $projectDetails): static
    {
        $this->projectDetails = $projectDetails;
        return $this;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function setPaymentIntentId(?string $paymentIntentId): static
    {
        $this->paymentIntentId = $paymentIntentId;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getIsPaid(): ?int
    {
        return $this->isPaid;
    }

    public function setIsPaid(?int $isPaid): static
    {
        $this->isPaid = $isPaid ?? self::PAYMENT_NOT_PAID;
        return $this;
    }

    public function isDepositPaid(): bool
    {
        return $this->isPaid === self::PAYMENT_DEPOSIT_PAID;
    }

    public function isFullyPaid(): bool
    {
        return $this->isPaid === self::PAYMENT_FULL_PAID;
    }

    public function isNotPaid(): bool
    {
        return $this->isPaid === self::PAYMENT_NOT_PAID;
    }

    public function getDepositAmount(): string
    {
        $amount = (float) $this->amount;
        return number_format($amount / 2, 2, '.', '');
    }

    public function getRemainingAmount(): string
    {
        if ($this->isPaid === self::PAYMENT_DEPOSIT_PAID) {
            $amount = (float) $this->amount;
            return number_format($amount / 2, 2, '.', '');
        }
        return $this->amount;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
