<?php

declare(strict_types=1);

namespace DddModule\Snapshotting\Snapshot;

use DddModule\Snapshotting\EventSourcing\AggregateAssemblerInterface;
use DddModule\Snapshotting\Snapshot\Storage\SnapshotNotFoundException;
use DddModule\Snapshotting\Snapshot\Storage\SnapshotStoreInterface;
use Assert\Assertion as Assert;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Throwable;

class SnapshotRepository implements SnapshotRepositoryInterface
{
    /**
     * @var SnapshotStoreInterface
     */
    private $snapshotStore;

    /**
     * @var string
     */
    private $aggregateClass;

    /**
     * @param SnapshotStoreInterface    $snapshotStore
     * @param string                    $aggregateClass
     */
    public function __construct(
        SnapshotStoreInterface $snapshotStore,
        string $aggregateClass
    ) {
        $this->assertExtendsEventSourcedAggregateRoot($aggregateClass);

        $this->snapshotStore = $snapshotStore;
        $this->aggregateClass = $aggregateClass;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    public function load($id): ?Snapshot
    {
        try {
            $domainMessage = $this->snapshotStore->load($id);

            return new Snapshot($domainMessage->getPlayhead(), $domainMessage->getPayload());
        } catch (SnapshotNotFoundException $e) {
            return null;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     *
     * @throws Throwable
     */
    public function getSnapshotPayload(string $id)
    {
        try {
            $domainMessage = $this->snapshotStore->load($id);

            return $domainMessage->getPayload();
        } catch (SnapshotNotFoundException $e) {
            return null;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(Snapshot $snapshot): void
    {
        $aggregate = $snapshot->getAggregateRoot();

        if (!$aggregate instanceof AggregateAssemblerInterface) {
            throw new RepositoryException('AggregateRoot not instance of AggregateAssemblerInterface');
        }
        // maybe we can get generics one day.... ;)
        Assert::isInstanceOf($aggregate, $this->aggregateClass);
        $this->snapshotStore->append($aggregate->getAggregateRootId(), DomainMessage::recordNow(
            $aggregate->getAggregateRootId(),
            $snapshot->getPlayhead(),
            new Metadata([]),
            $aggregate
        ));
    }

    /**
     * @param string $class
     */
    private function assertExtendsEventSourcedAggregateRoot(string $class): void
    {
        Assert::subclassOf(
            $class,
            EventSourcedAggregateRoot::class,
            sprintf("Class '%s' is not an EventSourcedAggregateRoot.", $class)
        );

        Assert::implementsInterface(
            $class,
            AggregateAssemblerInterface::class,
            sprintf("Class '%s' is not instanse of AggregateAssemblerInterface.", $class)
        );
    }
}
