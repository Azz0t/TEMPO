<?php

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le nom du projet est obligatoire.')]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    #[Assert\GreaterThan(propertyPath: 'startDate', message: 'La date de fin doit être après la date de début.')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, BoardMember>
     */
    #[ORM\OneToMany(targetEntity: BoardMember::class, mappedBy: 'board', orphanRemoval: true)]
    private Collection $boardMembers;

    /**
     * @var Collection<int, BoardColumn>
     */
    #[ORM\OneToMany(targetEntity: BoardColumn::class, mappedBy: 'board', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $boardColumns;

    /**
     * @var Collection<int, Sprint>
     */
    #[ORM\OneToMany(targetEntity: Sprint::class, mappedBy: 'board', orphanRemoval: true)]
    private Collection $sprints;

    /**
     * @var Collection<int, Epic>
     */
    #[ORM\OneToMany(targetEntity: Epic::class, mappedBy: 'board', orphanRemoval: true)]
    private Collection $epics;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->boardMembers = new ArrayCollection();
        $this->boardColumns = new ArrayCollection();
        $this->sprints = new ArrayCollection();
        $this->epics = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, BoardMember>
     */
    public function getBoardMembers(): Collection
    {
        return $this->boardMembers;
    }

    public function addBoardMember(BoardMember $boardMember): static
    {
        if (!$this->boardMembers->contains($boardMember)) {
            $this->boardMembers->add($boardMember);
            $boardMember->setBoard($this);
        }

        return $this;
    }

    public function removeBoardMember(BoardMember $boardMember): static
    {
        if ($this->boardMembers->removeElement($boardMember)) {
            // set the owning side to null (unless already changed)
            if ($boardMember->getBoard() === $this) {
                $boardMember->setBoard(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BoardColumn>
     */
    public function getBoardColumns(): Collection
    {
        return $this->boardColumns;
    }

    public function addBoardColumn(BoardColumn $boardColumn): static
    {
        if (!$this->boardColumns->contains($boardColumn)) {
            $this->boardColumns->add($boardColumn);
            $boardColumn->setBoard($this);
        }

        return $this;
    }

    public function removeBoardColumn(BoardColumn $boardColumn): static
    {
        if ($this->boardColumns->removeElement($boardColumn)) {
            // set the owning side to null (unless already changed)
            if ($boardColumn->getBoard() === $this) {
                $boardColumn->setBoard(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sprint>
     */
    public function getSprints(): Collection
    {
        return $this->sprints;
    }

    public function addSprint(Sprint $sprint): static
    {
        if (!$this->sprints->contains($sprint)) {
            $this->sprints->add($sprint);
            $sprint->setBoard($this);
        }

        return $this;
    }

    public function removeSprint(Sprint $sprint): static
    {
        if ($this->sprints->removeElement($sprint)) {
            // set the owning side to null (unless already changed)
            if ($sprint->getBoard() === $this) {
                $sprint->setBoard(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Epic>
     */
    public function getEpics(): Collection
    {
        return $this->epics;
    }

    public function addEpic(Epic $epic): static
    {
        if (!$this->epics->contains($epic)) {
            $this->epics->add($epic);
            $epic->setBoard($this);
        }

        return $this;
    }

    public function removeEpic(Epic $epic): static
    {
        if ($this->epics->removeElement($epic)) {
            // set the owning side to null (unless already changed)
            if ($epic->getBoard() === $this) {
                $epic->setBoard(null);
            }
        }

        return $this;
    }
}
