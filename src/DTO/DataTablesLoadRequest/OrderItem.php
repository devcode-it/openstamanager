<?php

declare(strict_types=1);

namespace DTO\DataTablesLoadRequest;

final class OrderItem
{
    /**
     * @param int         $column index into columns[]
     * @param string|null $name   optional columns[i].name
     */
    public function __construct(public int $column = 0, public ?string $name = null, public SortDirection $dir = SortDirection::ASC)
    {
    }

    public static function fromArray(array $input = []): self
    {
        $col = isset($input['column']) ? (int) $input['column'] : 0;
        $name = isset($input['name']) && $input['name'] !== '' ? (string) $input['name'] : null;
        $dirStr = isset($input['dir']) ? (string) $input['dir'] : SortDirection::ASC->value;

        return new self($col, $name, SortDirection::from($dirStr));
    }

    public function getColumnIndex(): int
    {
        return $this->column;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDir(): SortDirection
    {
        return $this->dir;
    }

    public function toArray(): array
    {
        return ['column' => $this->column, 'name' => $this->name, 'dir' => $this->dir->value];
    }
}
