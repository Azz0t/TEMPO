<?php

namespace App\Entity;

use App\Enum\EpicStatus;
use App\Repository\EpicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EpicRepository::class)]
class Epic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 7)]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, message: 'Choisis une couleur au format #RRGGBB.')]
    private ?string $color = null;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'La progression doit être entre 0 et 100.')]
    private ?int $progress = 0;

    #[ORM\ManyToOne(inversedBy: 'epics')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Board $board = null;

    #[ORM\Column(length: 20, enumType: EpicStatus::class)]
    private EpicStatus $status = EpicStatus::NOT_STARTED;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'epic')]
    private Collection $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): static
    {
        $this->progress = $progress;

        return $this;
    }

    public function getBoard(): ?Board
    {
        return $this->board;
    }

    public function setBoard(?Board $board): static
    {
        $this->board = $board;

        return $this;
    }

    public function getStatus(): EpicStatus
    {
        return $this->status;
    }

    public function setStatus(EpicStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setEpic($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            // set the owning side to null (unless already changed)
            if ($card->getEpic() === $this) {
                $card->setEpic(null);
            }
        }

        return $this;
    }
}