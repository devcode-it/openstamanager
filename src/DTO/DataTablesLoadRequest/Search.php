<?php

declare(strict_types=1);

namespace DTO\DataTablesLoadRequest;

final class Search
{
    public function __construct(public string $value, public bool $regex = false)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isRegex(): bool
    {
        return $this->regex;
    }

    public function toArray(): array
    {
        return ['value' => $this->value, 'regex' => $this->regex];
    }
}
