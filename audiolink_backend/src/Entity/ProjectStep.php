<?php

namespace App\Entity;

use App\Repository\ProjectStepRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProjectStepRepository::class)]
#[ORM\Table(name: 'project_steps', schema: 'audiolink')]
class ProjectStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['step:read', 'project:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectSteps')]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\Column(length: 100)]
    #[Groups(['step:read', 'project:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 20, options: ['default' => 'todo'])]
    #[Groups(['step:read', 'project:read'])]
    private ?string $status = 'todo';

    #[ORM\Column]
    #[Groups(['step:read', 'project:read'])]
    private ?int $position = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }
}
