<?php

declare(strict_types=1);

namespace DddModule\Snapshotting\EventSourcing;

use DddModule\ValueObject\ValueObjectInterface;

/**
 * Interface AggregateAssemblerInterface.
 */
interface AggregateAssemblerInterface
{
    /**
     * Assemble entity from value object.
     *
     * @param ValueObjectInterface $valueObject
     */
    public function assembleFromValueObject(ValueObjectInterface $valueObject): void;

    /**
     * Assemble value object from entity.
     *
     * @return ValueObjectInterface
     */
    public function assembleToValueObject(): ValueObjectInterface;
}
