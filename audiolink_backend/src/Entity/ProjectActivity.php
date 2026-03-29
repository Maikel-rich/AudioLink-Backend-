<?php

namespace App\Entity;

use App\Repository\ProjectActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectActivityRepository::class)]
#[ORM\Table(name: 'project_activity', schema: 'audiolink')]
class ProjectActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: "project_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id")]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
