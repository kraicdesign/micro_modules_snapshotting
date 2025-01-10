<?php

declare(strict_types=1);

namespace DddModule\Snapshotting\EventSourcing;

use DddModule\Snapshotting\Snapshot\Snapshot;
use DddModule\Snapshotting\Snapshot\SnapshotRepositoryInterface;
use DddModule\Snapshotting\Snapshot\TriggerInterface;
use Broadway\Domain\AggregateRoot;
use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\EventStore;
use Broadway\Repository\Repository;

class SnapshottingEventSourcingRepository implements Repository
{
    /**
     * Event sourcing repository object
     */
    protected EventSourcingRepository $eventSourcingRepository;

    /**
     * Event store object
     */
    protected EventStore $eventStore;

    /**
     * SnapshotRepositoryInterface
     */
    protected SnapshotRepositoryInterface $snapshotRepository;

    /**
     * TriggerInterface object
     */
    protected TriggerInterface $trigger;

    /**
     * SnapshottingEventSourcingRepository constructor.
     *
     * @param EventSourcingRepository     $eventSourcingRepository
     * @param EventStore                  $eventStore
     * @param SnapshotRepositoryInterface $snapshotRepository
     * @param TriggerInterface            $trigger
     */
    public function __construct(
        EventSourcingRepository $eventSourcingRepository,
        EventStore $eventStore,
        SnapshotRepositoryInterface $snapshotRepository,
        TriggerInterface $trigger
    ) {
        $this->eventSourcingRepository = $eventSourcingRepository;
        $this->eventStore = $eventStore;
        $this->snapshotRepository = $snapshotRepository;
        $this->trigger = $trigger;
    }

    /**
     * {@inheritdoc}
     */
    public function load($id): AggregateRoot
    {
        $snapshot = $this->snapshotRepository->load($id);

        if (null === $snapshot) {
            return $this->eventSourcingRepository->load($id);
        }
        $aggregateRoot = $snapshot->getAggregateRoot();
        $domainEventStream = $this->eventStore->loadFromPlayhead($id, $snapshot->getPlayhead() + 1);
        $aggregateRoot->initializeStateFromSnapshot($snapshot, $domainEventStream);

        return $aggregateRoot;
    }

    /**
     * {@inheritdoc}
     * @throws SnapshottingEventSourcingRepositoryException
     */
    public function save(AggregateRoot $aggregate): void
    {
        if (!$aggregate instanceof EventSourcedAggregateRoot) {
            throw new SnapshottingEventSourcingRepositoryException('Aggregate not instance of EventSourcedAggregateRoot');
        }

        if ($this->trigger->shouldSnapshot($aggregate)) {
            $this->snapshotRepository->save(
                new Snapshot($aggregate->getPlayhead(), $aggregate)
            );
        }
        $this->eventSourcingRepository->save($aggregate);
    }
}
