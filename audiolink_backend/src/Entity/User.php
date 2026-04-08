<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users', schema: 'audiolink')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_PRODUCER = 0;
    public const ROLE_ARTIST = 1;
    public const ROLE_ADMIN = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment:read', 'message:read', 'project:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comment:read', 'message:read', 'user:read'])]
    private ?string $email = null;

    #[ORM\Column(name: "password_hash", type: Types::TEXT)]
    private ?string $password = null;

    #[ORM\Column(name: "full_name", length: 100, nullable: true)]
    #[Groups(['comment:read', 'message:read', 'project:read', 'user:read'])]
    private ?string $fullName = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['comment:read', 'user:read'])]
    private int $role = self::ROLE_ARTIST;

    #[ORM\Column(name: "avatar_url", type: Types::TEXT, nullable: true)]
    #[Groups(['comment:read', 'message:read', 'user:read'])]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $bio = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['user:read'])]
    private ?array $genres = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['user:read'])]
    private ?array $languages = null;

    #[ORM\Column(name: "is_verified", options: ["default" => false], nullable: true)]
    #[Groups(['user:read'])]
    private ?bool $isVerified = false;

    #[ORM\Column(name: "total_streams", length: 20, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $totalStreams = null;

    #[ORM\Column(name: "years_experience", nullable: true)]
    #[Groups(['user:read'])]
    private ?int $yearsExperience = null;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->role === self::ROLE_ADMIN) {
            $roles[] = 'ROLE_ADMIN';
        } elseif ($this->role === self::ROLE_PRODUCER) {
            $roles[] = 'ROLE_PRODUCER';
        } elseif ($this->role === self::ROLE_ARTIST) {
            $roles[] = 'ROLE_ARTIST';
        }
        return array_unique($roles);
    }

    public function getRole(): int
    {
        return $this->role;
    }

    public function setRole(int $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getGenres(): ?array
    {
        return $this->genres;
    }

    public function setGenres(?array $genres): static
    {
        $this->genres = $genres;
        return $this;
    }

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function setLanguages(?array $languages): static
    {
        $this->languages = $languages;
        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(?bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getTotalStreams(): ?string
    {
        return $this->totalStreams;
    }

    public function setTotalStreams(?string $totalStreams): static
    {
        $this->totalStreams = $totalStreams;
        return $this;
    }

    public function getYearsExperience(): ?int
    {
        return $this->yearsExperience;
    }

    public function setYearsExperience(?int $yearsExperience): static
    {
        $this->yearsExperience = $yearsExperience;
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

    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['password']);
        return $vars;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }
}
