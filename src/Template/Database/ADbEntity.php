<?php
declare(strict_types=1);

namespace Kentron\Template\Database;

// Services
use Kentron\Facade\DT;
use Kentron\Support\Type\Type;

// Entities
use Kentron\Template\Entity\AEntity;
use Kentron\Template\Entity\ACoreEntity;

abstract class ADbEntity extends ACoreEntity
{
    /** Name of the table, needs to be overridden */
    public const TABLE = "";
    /** Name of the table primary column */
    public const COLUMN_ID = "id";
    /** Name of the table's created datetime column */
    public const COLUMN_CREATED_AT = "created_at";
    /** Name of the table's deleted datetime column */
    public const COLUMN_DELETED_AT = "deleted_at";

    // DB props
    public int $id;
    public DT|null $createdAt;
    public DT|null $deletedAt;

    public function __construct()
    {
        // Set default getters and setters for common table columns
        $this->addSetterAndGetter($this::COLUMN_ID);
        $this->addSetterAndGetter($this::COLUMN_CREATED_AT);
        $this->addSetterAndGetter($this::COLUMN_DELETED_AT);
    }

    /**
     * Generator to loop through the available properties specific to builing the table
     *
     * @param boolean $allowNullable Allows null values to be returned
     *
     * @return iterable
     */
    public function iterateAvailableProperties(bool $allowNullable = false): iterable
    {
        foreach ($this->iterateProperties($allowNullable) as $property => $value) {
            if (
                (is_null($value) && !$allowNullable) ||
                (is_object($value) && is_subclass_of($value, AEntity::class))
            ) {
                // Don't return entities
                continue;
            }

            if ($value instanceof DT) {
                $value = $value->format();
            }

            yield $property => $value;
        }
    }

    /**
     * Getters
     */

    public function getCreatedAt(): ?DT
    {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?DT
    {
        return $this->deletedAt;
    }

    /**
     * Setters
     */

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = is_string($createdAt) ? DT::then($createdAt) : null;
    }

    public function setDeletedAt(?string $deletedAt): void
    {
        $this->deletedAt = is_string($deletedAt) ? DT::then($deletedAt) : null;
    }

    /**
     * Private methods
     */

    /**
     * Attach a getter/setter or prop binding to the $propertyMap
     *
     * @param string $property
     *
     * @return void
     */
    private function addSetterAndGetter(string $property): void
    {
        $pascal = str_replace('_', '', ucwords($property, '_'));
        $camel = lcfirst($pascal);

        if (property_exists($this, $camel)) {
            if (Type::isAssoc($this->propertyMap)) {
                $this->propertyMap[$property]["prop"] = $camel;
            }
            else {
                $this->propertyMap[$property] = $camel;
            }
            return;
        }

        $getter = "get{$pascal}";
        $setter = "set{$pascal}";

        if ($this->isValidMethod($getter)) {
            if (!Type::isAssoc($this->propertyMap)) {
                throw new \Error("Property map for " . $this::class . " must be associative, or remove method {$getter}()");
            }

            $this->propertyMap[$property]["get"] = $getter;
        }
        if ($this->isValidMethod($setter)) {
            if (!Type::isAssoc($this->propertyMap)) {
                throw new \Error("Property map for " . $this::class . " must be associative, or remove method {$setter}()");
            }

            $this->propertyMap[$property]["set"] = $setter;
        }
    }
}
