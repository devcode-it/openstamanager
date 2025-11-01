<?php
declare(strict_types=1);

namespace DTO\DataTablesLoadRequest;

final class Search
{
	public function __construct(public string $value, public bool $regex = false)
	{}

	public static function fromArray(array $input = []): self
	{
		$value = isset($input['value']) ? (string)$input['value'] : '';
		$regex = isset($input['regex']) ? filter_var($input['regex'], FILTER_VALIDATE_BOOLEAN) : false;
		return new self($value, $regex);
	}

	public function getValue(): string { return $this->value; }
	public function isRegex(): bool { return $this->regex; }

	public function toArray(): array
	{
		return ['value' => $this->value, 'regex' => $this->regex];
	}
}
