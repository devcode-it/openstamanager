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

    public static function fromArray(array $input = []): self
    {
        $data = isset($input['data']) ? (string) $input['data'] : '';
        $name = isset($input['name']) ? (string) $input['name'] : null;
        $searchable = isset($input['searchable']) ? filter_var($input['searchable'], FILTER_VALIDATE_BOOLEAN) : true;
        $orderable = isset($input['orderable']) ? filter_var($input['orderable'], FILTER_VALIDATE_BOOLEAN) : true;
        $search = isset($input['search']) && is_array($input['search'])
            ? Search::fromArray($input['search'])
            : null;

        return new self($data, $name, $searchable, $orderable, $search);
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
