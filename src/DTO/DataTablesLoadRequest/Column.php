<?php

declare(strict_types=1);

namespace DTO\DataTablesLoadRequest;

final class Column
{
    public function __construct(
        public string $data,
        public ?string $name = null,
        public bool $searchable = true,
        public bool $orderable = true,
        public ?Search $search = null,
    ) {
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isOrderable(): bool
    {
        return $this->orderable;
    }

    public function getSearch(): ?Search
    {
        return $this->search;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'name' => $this->name,
            'searchable' => $this->searchable,
            'orderable' => $this->orderable,
            'search' => $this->search->toArray(),
        ];
    }
}
