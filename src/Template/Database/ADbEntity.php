<?php
declare(strict_types=1);

namespace Kentron\Template\Database;

use \Error;

// Services
use Kentron\Facade\DT;
use Kentron\Support\Type\Type;

// Entities
use Kentron\Template\Entity\AEntity;
use Kentron\Template\Entity\ACoreEntity;

abstract class ADbEntity extends ACoreEntity
{
    // DB props
    public int $id;
    public DT|null $createdAt = null;
    public DT|null $updatedAt = null;
    public DT|null $deletedAt = null;

    protected static AModel|string $modelClass = AModel::class;

    public function __construct()
    {
        foreach ($this->modelClass::getColumns() as $column) {
            $this->addSetterAndGetter($column);
        }
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
     * @param string|null $property
     *
     * @return void
     */
    private function addSetterAndGetter(?string $property): void
    {
        if (is_null($property)) {
            return;
        }

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
                throw new Error("Property map for " . $this::class . " must be associative, or remove method {$getter}()");
            }

            $this->propertyMap[$property]["get"] = $getter;
        }
        if ($this->isValidMethod($setter)) {
            if (!Type::isAssoc($this->propertyMap)) {
                throw new Error("Property map for " . $this::class . " must be associative, or remove method {$setter}()");
            }

            $this->propertyMap[$property]["set"] = $setter;
        }
    }
}
