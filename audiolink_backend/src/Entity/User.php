<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[Groups(['comment:read', 'message:read', 'user:read', 'project:read'])]
    private ?string $fullName = null;

    private array $roles = [];

    #[ORM\Column(name: "role", nullable: true)]
    #[Groups(['user:read'])]
    private ?int $role = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $bio = null;

    #[Groups(['user:read'])]
    private ?string $locationCity = null;

    #[Groups(['user:read'])]
    private ?string $locationCountry = null;

    #[ORM\Column(name: "avatar_url", length: 255, nullable: true)]
    #[Groups(['user:read', 'project:read'])]
    private ?string $profilePicture = null;

    #[ORM\Column(name: "genres", type: Types::JSON, nullable: true)]
    #[Groups(['user:read'])]
    private ?array $genres = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['user:read'])]
    private ?array $languages = [];

    #[ORM\Column(name: "is_verified", options: ["default" => false])]
    #[Groups(['user:read'])]
    private ?bool $isVerified = false;

    #[ORM\Column(name: "total_streams", type: Types::BIGINT, nullable: true, options: ["default" => 0])]
    #[Groups(['user:read'])]
    private ?string $totalStreams = '0';

    #[ORM\Column(name: "years_experience", nullable: true)]
    #[Groups(['user:read'])]
    private ?int $yearsExperience = null;

    #[ORM\Column(name: "presentation_audio_url", type: Types::TEXT, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $presentationAudioUrl = null;

    #[ORM\OneToMany(mappedBy: 'producer', targetEntity: BeatCatalog::class, cascade: ['remove'])]
    private Collection $beats;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->beats = new ArrayCollection();
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
        $roles = $this->roles;

        if ($this->role === self::ROLE_PRODUCER) {
            $roles[] = 'ROLE_PRODUCER';
        } elseif ($this->role === self::ROLE_ARTIST) {
            $roles[] = 'ROLE_ARTIST';
        } elseif ($this->role === self::ROLE_ADMIN) {
            $roles[] = 'ROLE_ADMIN';
        }

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        if (in_array('ROLE_ADMIN', $roles)) {
            $this->role = self::ROLE_ADMIN;
        } elseif (in_array('ROLE_ARTIST', $roles)) {
            $this->role = self::ROLE_ARTIST;
        } elseif (in_array('ROLE_PRODUCER', $roles)) {
            $this->role = self::ROLE_PRODUCER;
        }

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

    public function getRole(): ?int
    {
        return $this->role;
    }

    public function setRole(?int $role): static
    {
        $this->role = $role;
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

    public function getLocationCity(): ?string
    {
        return $this->locationCity;
    }

    public function setLocationCity(?string $locationCity): static
    {
        $this->locationCity = $locationCity;
        return $this;
    }

    public function getLocationCountry(): ?string
    {
        return $this->locationCountry;
    }

    public function setLocationCountry(?string $locationCountry): static
    {
        $this->locationCountry = $locationCountry;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
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

    public function getPresentationAudioUrl(): ?string
    {
        return $this->presentationAudioUrl;
    }

    public function setPresentationAudioUrl(?string $presentationAudioUrl): static
    {
        $this->presentationAudioUrl = $presentationAudioUrl;
        return $this;
    }

    public function getBeats(): Collection
    {
        return $this->beats;
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
}
