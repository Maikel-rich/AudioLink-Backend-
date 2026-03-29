<?php

namespace App\Entity;

use App\Repository\ProjectStepRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectStepRepository::class)]
#[ORM\Table(name: 'project_steps', schema: 'audiolink')]
class ProjectStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: "project_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?Project $project = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * Valores permitidos: 'todo', 'doing', 'done'
     */
    #[ORM\Column(length: 20, options: ["default" => "todo"])]
    private ?string $status = 'todo';

    #[ORM\Column]
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
        // Validamos el status antes de guardarlo (opcional pero recomendado)
        if (!in_array($status, ['todo', 'doing', 'done'])) {
            throw new \InvalidArgumentException("Invalid status");
        }
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
