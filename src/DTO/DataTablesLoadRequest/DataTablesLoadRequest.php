<?php
declare(strict_types=1);

namespace DTO\DataTablesLoadRequest;

final class DataTablesLoadRequest
{
	public int $draw;
	public int $start;
	public int $length;
	/**
	* @var Search
	*/
	public array $search; // Deserialization is not working correctly
	/**
	* @var OrderItem[]
	*/
	public array $order = []; // Deserialization is not working correctly
	/**
	* @var Column[]
	*/
	public array $columns = []; // Deserialization is not working correctly
	public ?string $_ = null;

	public function getDraw(): int { return $this->draw; }
	public function getStart(): int { return $this->start; }
	public function getLength(): int { return $this->length; }
	public function getSearch(): Search { return $this->search; }
	/** @return OrderItem[] */
	public function getOrder(): array { return $this->order; }
	/** @return Column[] */
	public function getColumns(): array { return $this->columns; }

	public function toArray(): array
	{
		return [
			'draw' => $this->draw,
			'start' => $this->start,
			'length' => $this->length,
			'search' => $this->search->toArray(),
			'order' => array_map(function (OrderItem $o) { return $o->toArray(); }, $this->order),
			'columns' => array_map(function (Column $c) { return $c->toArray(); }, $this->columns),
			'_' => $this->_,
		];
	}
}
