<?php
declare(strict_types=1);

namespace Kentron\Template\Database;

use \Error;
use \ReflectionType;

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
     * Private methods
     */

    /**
     * Attach a getter/setter or prop binding to the $propertyMap
     *
     * @param string|null $column
     *
     * @return void
     */
    private function addSetterAndGetter(?string $column): void
    {
        if (is_null($column)) {
            return;
        }

        // If this column has already been set in the DB map, skip it
        if (isset($this->propertyMap[$column])) {
            return;
        }

        $pascal = str_replace('_', '', ucwords($column, '_'));
        $camel = lcfirst($pascal);

        $reflectionProperty = $this->getReflectionClass()?->getProperty($camel);

        // If the property exists and is public
        if (!is_null($reflectionProperty) && $reflectionProperty->isPublic()) {
            // If the property is typed
            if ($reflectionProperty->getType() instanceof ReflectionType) {
                $this->propertyMap[$column] = $camel;
            }
            else {
                $this->propertyMap[$column]["prop"] = $camel;
            }
            return;
        }

        $getter = "get{$pascal}";
        $setter = "set{$pascal}";

        if ($this->isValidMethod($getter)) {
            if (!Type::isAssoc($this->propertyMap)) {
                throw new Error("Property map for " . $this::class . " must be associative, or remove method {$getter}()");
            }

            $this->propertyMap[$column]["get"] = $getter;
        }
        if ($this->isValidMethod($setter)) {
            if (!Type::isAssoc($this->propertyMap)) {
                throw new Error("Property map for " . $this::class . " must be associative, or remove method {$setter}()");
            }

            $this->propertyMap[$column]["set"] = $setter;
        }
    }
}
