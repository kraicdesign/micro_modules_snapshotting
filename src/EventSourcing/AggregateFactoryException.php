<?php

declare(strict_types=1);

namespace DddModule\Snapshotting\EventSourcing;

use DddModule\Base\Domain\Exception\CriticalException;

/**
 * Class AggregateFactoryException.
 */
class AggregateFactoryException extends CriticalException
{
}
