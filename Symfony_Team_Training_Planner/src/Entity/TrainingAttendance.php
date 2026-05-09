<?php

namespace App\Entity;

use App\Repository\TrainingAttendanceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingAttendanceRepository::class)]
#[ORM\UniqueConstraint(name: 'training_participant_unique', columns: ['training_id', 'participant_id'])]
class TrainingAttendance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\ManyToOne(inversedBy: 'trainingAttendances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $participant = null;

    #[ORM\ManyToOne(inversedBy: 'attendances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Training $training = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getParticipant(): ?User
    {
        return $this->participant;
    }

    public function setParticipant(?User $participant): static
    {
        $this->participant = $participant;

        return $this;
    }

    public function getTraining(): ?Training
    {
        return $this->training;
    }

    public function setTraining(?Training $training): static
    {
        $this->training = $training;

        return $this;
    }
}
