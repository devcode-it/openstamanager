<?php

declare(strict_types=1);

namespace DTO\DataTablesLoadRequest;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';
}
