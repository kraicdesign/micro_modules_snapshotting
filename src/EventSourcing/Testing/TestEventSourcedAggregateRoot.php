<?php

declare(strict_types=1);

namespace DddModule\Snapshotting\EventSourcing\Testing;

use Broadway\EventSourcing\EventSourcedAggregateRoot;

class TestEventSourcedAggregateRoot extends EventSourcedAggregateRoot
{
    /**
     * {@inheritdoc}
     */
    public function getAggregateRootId(): string
    {
        return '42';
    }
}
