<?php

namespace App\Entity;

use App\Enum\BoardRole;
use App\Repository\BoardMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: BoardMemberRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_board_user', columns: ['board_id', 'user_id'])]
#[UniqueEntity(fields: ['board', 'user'], message: 'Cet utilisateur est déjà membre de ce tableau.')]
class BoardMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'boardMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Board $board = null;

    #[ORM\ManyToOne(inversedBy: 'boardMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20, enumType: BoardRole::class)]
    private ?BoardRole $role = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRole(): ?BoardRole
    {
        return $this->role;
    }

    public function setRole(BoardRole $role): static
    {
        $this->role = $role;

        return $this;
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
}