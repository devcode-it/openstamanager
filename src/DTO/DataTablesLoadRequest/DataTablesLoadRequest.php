<?php

declare(strict_types=1);

namespace DTO\DataTablesLoadRequest;

use Symfony\Component\Serializer\Attribute\Ignore;

final class DataTablesLoadRequest
{
    // Private properties are from the route
    // We must use Ignore on the getters to prevent them from being serialized
    private ?int $id_module = 0;
    private ?int $id_plugin = 0;
    private ?int $id_parent = 0;

    public int $draw = 0;
    public int $start = 0;
    public int $length = 200;

    public Search $search;
    /**
     * @var OrderItem[]
     */
    public array $order = [];
    /**
     * @var Column[]
     */
    public array $columns = [];
    public ?string $_ = null;

    #[Ignore]
    public function getIdModule(): int
    {
        return $this->id_module;
    }

    #[Ignore]
    public function getIdPlugin(): int
    {
        return $this->id_plugin;
    }

    #[Ignore]
    public function getIdParent(): int
    {
        return $this->id_parent;
    }

    public function getDraw(): int
    {
        return $this->draw;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getSearch(): Search
    {
        return $this->search;
    }

    /** @return OrderItem[] */
    public function getOrder(): array
    {
        return $this->order;
    }

    /** @return Column[] */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function toArray(): array
    {
        return [
            'draw' => $this->draw,
            'start' => $this->start,
            'length' => $this->length,
            'search' => $this->search->toArray(),
            'order' => array_map(fn (OrderItem $o) => $o->toArray(), $this->order),
            'columns' => array_map(fn (Column $c) => $c->toArray(), $this->columns),
            '_' => $this->_,
        ];
    }
}
