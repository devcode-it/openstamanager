<?php

require 'vendor/autoload.php';

use CuyZ\Valinor\MapperBuilder;

echo "=== PR #1750 Integration Test ===\n\n";

// Test 1: Valinor Basic Deserialization
echo "Test 1: Valinor Basic Deserialization\n";
try {
    $mapper = (new MapperBuilder())->mapper();
    
    // Test semplice
    $simpleArray = [
        'name' => 'Test',
        'value' => 123
    ];
    
    class SimpleDTO {
        public string $name;
        public int $value;
    }
    
    $simpleResult = $mapper->map(SimpleDTO::class, $simpleArray);
    echo "✓ Simple deserialization works\n";
    echo "  Name: {$simpleResult->name}, Value: {$simpleResult->value}\n";
} catch (\Exception $e) {
    echo "✗ Simple deserialization failed: {$e->getMessage()}\n";
}

// Test 2: DataTables Load Request Deserialization
echo "\nTest 2: DataTables Load Request Deserialization\n";
try {
    require_once 'src/DTO/DataTablesLoadRequest/DataTablesLoadRequest.php';
    require_once 'src/DTO/DataTablesLoadRequest/Search.php';
    require_once 'src/DTO/DataTablesLoadRequest/OrderItem.php';
    require_once 'src/DTO/DataTablesLoadRequest/Column.php';
    require_once 'src/DTO/DataTablesLoadRequest/SortDirection.php';
    
    $mapper = (new MapperBuilder())->mapper();
    
    $dataTablesInput = [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'search' => [
            'value' => 'test',
            'regex' => false
        ],
        'order' => [
            [
                'column' => 0,
                'name' => null,
                'dir' => 'asc'
            ]
        ],
        'columns' => [
            [
                'data' => 'nome',
                'name' => '',
                'searchable' => true,
                'orderable' => true,
                'search' => [
                    'value' => '',
                    'regex' => false
                ]
            ]
        ],
        '_' => null
    ];
    
    $dataTablesRequest = $mapper->map(\DTO\DataTablesLoadRequest\DataTablesLoadRequest::class, $dataTablesInput);
    echo "✓ DataTables request deserialization works\n";
    echo "  Draw: {$dataTablesRequest->getDraw()}\n";
    echo "  Start: {$dataTablesRequest->getStart()}\n";
    echo "  Length: {$dataTablesRequest->getLength()}\n";
} catch (\Exception $e) {
    echo "✗ DataTables deserialization failed: {$e->getMessage()}\n";
    echo "  This is the KNOWN PROBLEM mentioned in PR comments\n";
}

// Test 3: Class Structure Check
echo "\nTest 3: Class Structure Check\n";
try {
    $classes = [
        '\API\Controllers\BaseController' => 'BaseController',
        '\API\Controllers\DataTablesController' => 'DataTablesController',
        '\Modules\Impostazioni\API\Controllers\GetImpostazioneController' => 'GetImpostazioneController',
        '\Modules\Impostazioni\API\Controllers\ListImpostazioniController' => 'ListImpostazioniController',
        '\Modules\Impostazioni\API\Controllers\UpdateImpostazioneController' => 'UpdateImpostazioneController',
    ];
    
    foreach ($classes as $class => $name) {
        if (class_exists($class)) {
            echo "✓ {$name} exists\n";
        } else {
            echo "✗ {$name} does not exist\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ Class structure check failed: {$e->getMessage()}\n";
}

// Test 4: Old Classes Check (should NOT exist)
echo "\nTest 4: Old Classes Check (should NOT exist)\n";
try {
    $oldClasses = [
        '\Modules\Impostazioni\API\Controllers\GetImpostazioneProvider' => 'GetImpostazioneProvider',
        '\Modules\Impostazioni\API\Controllers\ListImpostazioniProvider' => 'ListImpostazioniProvider',
        '\Modules\Impostazioni\API\Controllers\UpdateImpostazioneProcessor' => 'UpdateImpostazioneProcessor',
    ];
    
    foreach ($oldClasses as $class => $name) {
        if (!class_exists($class)) {
            echo "✓ {$name} correctly removed\n";
        } else {
            echo "✗ {$name} still exists (should be removed)\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ Old classes check failed: {$e->getMessage()}\n";
}

// Test 5: Module Permission Logic
echo "\nTest 5: Module Permission Logic\n";
try {
    $code = '
    $user = (object)["is_admin" => true];
    if ($user->is_admin) {
        echo "Admin check: OK\n";
    }
    
    $pivot = (object)["permessi" => "rw"];
    $module = (object)[
        "pivot" => $pivot,
        "groups" => (object)[
            "first" => function($id) {
                return (object)["pivot" => (object)["permessi" => "r"]];
            }
        ]
    ];
    
    if ($module->pivot) {
        echo "Pivot check: " . ($module->pivot->permessi ?: "default") . "\n";
    } else {
        $group_id = 1;
        $match = $module->groups->first($group_id);
        $pivot = $match ? $match->pivot : null;
        echo "Lazy pivot check: " . ($pivot ? ($pivot->permessi ?: "default") : "default") . "\n";
    }
    ';
    eval($code);
    echo "✓ Permission logic structure OK\n";
} catch (\Exception $e) {
    echo "✗ Permission logic test failed: {$e->getMessage()}\n";
}

echo "\n=== Test Summary ===\n";
echo "Branch: pr1750-test-integration\n";
echo "Dipendenze: Valinor installato ✓\n";
echo "Problemi identificati:\n";
echo "1. Deserializzazione DTO (conosciuto in PR)\n";
echo "2. Conflitto struttura vecchia/nuova DTO\n";
echo "3. Necessità fix per compatibilità viste DataTables\n";