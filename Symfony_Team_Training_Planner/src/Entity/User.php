<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Training>
     */
    #[ORM\OneToMany(targetEntity: Training::class, mappedBy: 'coach')]
    private Collection $trainings;

    /**
     * @var Collection<int, TrainingAttendance>
     */
    #[ORM\OneToMany(targetEntity: TrainingAttendance::class, mappedBy: 'participant')]
    private Collection $trainingAttendances;

    public function __construct()
    {
        $this->trainings = new ArrayCollection();
        $this->trainingAttendances = new ArrayCollection();
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    /**
     * @return Collection<int, Training>
     */
    public function getTrainings(): Collection
    {
        return $this->trainings;
    }

    public function addTraining(Training $training): static
    {
        if (!$this->trainings->contains($training)) {
            $this->trainings->add($training);
            $training->setCoach($this);
        }

        return $this;
    }

    public function removeTraining(Training $training): static
    {
        $this->trainings->removeElement($training);

        return $this;
    }

    /**
     * @return Collection<int, TrainingAttendance>
     */
    public function getTrainingAttendances(): Collection
    {
        return $this->trainingAttendances;
    }

    public function addTrainingAttendance(TrainingAttendance $trainingAttendance): static
    {
        if (!$this->trainingAttendances->contains($trainingAttendance)) {
            $this->trainingAttendances->add($trainingAttendance);
            $trainingAttendance->setParticipant($this);
        }

        return $this;
    }

    public function removeTrainingAttendance(TrainingAttendance $trainingAttendance): static
    {
        $this->trainingAttendances->removeElement($trainingAttendance);
        return $this;
    }
}
