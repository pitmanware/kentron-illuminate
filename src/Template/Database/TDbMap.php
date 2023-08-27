<?php
declare(strict_types=1);

namespace Kentron\Template\Database;

use Kentron\Facade\DT;

trait TDbMap
{
    public ?int $id;
    public ?DT $createdAt;
    public ?DT $updatedAt;
    public ?DT $deletedAt;

    // Getters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?DT
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DT
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DT
    {
        return $this->deletedAt;
    }

    // Setters

    public function setId (int $id): void
    {
        $this->id = $id;
    }

    public function setCreatedAt (string|DT|null $createdAt): void
    {
        $this->createdAt = is_string($createdAt) ? DT::then($createdAt) : $createdAt;
    }

    public function setUpdatedAt (string|DT|null $updatedAt): void
    {
        $this->updatedAt = is_string($updatedAt) ? DT::then($updatedAt) : $updatedAt;
    }

    public function setDeletedAt (string|DT|null $deletedAt): void
    {
        $this->deletedAt = is_string($deletedAt) ? DT::then($deletedAt) : $deletedAt;
    }
}
