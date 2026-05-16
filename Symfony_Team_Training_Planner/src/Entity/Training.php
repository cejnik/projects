<?php

namespace App\Entity;

use App\Repository\TrainingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrainingRepository::class)]
class Training
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Title is required.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Training date is required.')]
    #[Assert\GreaterThan('now', message: 'Training must be scheduled in the future.')]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Location is required.')]
    private ?string $location = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Capacity is required.')]
    #[Assert\Positive(message: 'Capacity must be greater than 0.')]
    private ?int $capacity = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'trainings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coach = null;

    /**
     * @var Collection<int, TrainingAttendance>
     */
    #[ORM\OneToMany(targetEntity: TrainingAttendance::class, mappedBy: 'training')]
    private Collection $attendances;

    /**
     * @var Collection<int, TrainingComment>
     */
    #[ORM\OneToMany(targetEntity: TrainingComment::class, mappedBy: 'training')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    public function __construct()
    {
        $this->attendances = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCoach(): ?User
    {
        return $this->coach;
    }

    public function setCoach(?User $coach): static
    {
        $this->coach = $coach;

        return $this;
    }

    /**
     * @return Collection<int, TrainingAttendance>
     */
    public function getAttendances(): Collection
    {
        return $this->attendances;
    }

    public function addAttendance(TrainingAttendance $attendance): static
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances->add($attendance);
            $attendance->setTraining($this);
        }

        return $this;
    }

    public function removeAttendance(TrainingAttendance $attendance): static
    {
        $this->attendances->removeElement($attendance);
        return $this;
    }

    /**
     * @return Collection<int, TrainingComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(TrainingComment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTraining($this);
        }

        return $this;
    }

    public function removeComment(TrainingComment $comment): static
    {
        $this->comments->removeElement($comment);
        return $this;
    }
}
