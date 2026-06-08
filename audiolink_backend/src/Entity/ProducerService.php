<?php

namespace App\Entity;

use App\Repository\ProducerServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProducerServiceRepository::class)]
#[ORM\Table(name: 'producer_services', schema: 'audiolink')]
class ProducerService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['service:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'producer_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Groups(['service:read'])]
    private ?User $producer = null;

    #[ORM\Column(length: 100)]
    #[Groups(['service:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['service:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['service:read'])]
    private ?string $price = null;

    #[ORM\Column(name: "delivery_time_days", nullable: true)]
    #[Groups(['service:read'])]
    private ?int $deliveryTimeDays = null;

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
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): static
    {
        $this->description = $description;
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
    public function getDeliveryTimeDays(): ?int
    {
        return $this->deliveryTimeDays;
    }
    public function setDeliveryTimeDays(?int $deliveryTimeDays): static
    {
        $this->deliveryTimeDays = $deliveryTimeDays;
        return $this;
    }
}
