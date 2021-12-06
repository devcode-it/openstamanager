<?php

namespace Plugins\PresentazioniBancarie\RiBa\Records;

interface RecordInterface
{
    public static function getStruttura(): array;

    public static function getCodice(): string;

    public function get(string $name): ?string;

    public function set(string $name, ?string $value): void;

    public function toCBI(): string;

    public function fromCBI(string $contenuto): void;
}
