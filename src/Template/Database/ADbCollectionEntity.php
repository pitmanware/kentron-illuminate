<?php
declare(strict_types=1);

namespace Kentron\Template\Database;

use Kentron\Template\Entity\ACoreCollectionEntity;
use Kentron\Template\Entity\AEntity;

abstract class ADbCollectionEntity extends ACoreCollectionEntity
{
    /**
     * Overrides
     */

    /**
     * @return ADbEntity
     */
    public function newEntity(): ADbEntity
    {
        return parent::newEntity(...func_get_args());
    }

    /**
     * @return ADbEntity|null
     */
    public function getEntity(int $index): ?ADbEntity
    {
        return parent::getEntity($index);
    }

    /**
     * @return ADbEntity $entity
     */
    public function addNewEntity(): ADbEntity
    {
        return parent::addNewEntity();
    }

    /**
     * @return ADbEntity[]
     */
    public function iterateEntities(): iterable
    {
        yield from parent::iterateEntities();
    }

    /**
     * @return ADbEntity[]
     */
    public function filter(array $conditions): iterable
    {
        yield from parent::filter($conditions);
    }

    /**
     * @return ADbEntity|null
     */
    public function filterFirst(array $conditions, bool $and = true): ?ADbEntity
    {
        return parent::filterFirst($conditions, $and);
    }

    /**
     * @return ADbEntity[]
     */
    public function groupBy(string $methodOrProperty): iterable
    {
        yield from parent::groupBy($methodOrProperty);
    }

    /**
     * @return ADbEntity|null
     */
    public function shiftEntity(): ?ADbEntity
    {
        return parent::shiftEntity();
    }

    /**
     * @return ADbEntity|null
     */
    public function popEntity(): ?ADbEntity
    {
        return parent::popEntity();
    }

    /**
     * @return ADbEntity[]
     */
    public function getEntities(): array
    {
        return parent::getEntities();
    }

    /**
     * @return ADbEntity|null
     */
    public function getLast(): ?ADbEntity
    {
        return parent::getLast();
    }

    /**
     * @return ADbEntity|null
     */
    public function getFirst(): ?ADbEntity
    {
        return parent::getFirst();
    }

    /**
     * @return ADbEntity|self The translated entity
     */
    public function translate(AEntity $aEntity, bool $clobber = true): ADbEntity
    {
        return parent::translate($aEntity, $clobber);
    }
}
