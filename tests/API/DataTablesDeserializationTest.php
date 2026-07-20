<?php

declare(strict_types=1);

namespace Tests\API;

use CuyZ\Valinor\MapperBuilder;
use DTO\DataTablesLoadRequest\DataTablesLoadRequest;
use PHPUnit\Framework\TestCase;

class DataTablesDeserializationTest extends TestCase
{
    public function testValinorDeserialization()
    {
        $jsonInput = '{
            "draw": 1,
            "start": 0,
            "length": 25,
            "search": {"value": "test", "regex": false},
            "order": [{"column": 0, "dir": "asc"}],
            "columns": [
                {"data": "nome", "name": "", "searchable": true, "orderable": true, "search": {"value": "", "regex": false}}
            ]
        }';

        $mapper = (new MapperBuilder())->mapper();
        
        try {
            $dataTablesRequest = $mapper->map(DataTablesLoadRequest::class, json_decode($jsonInput, true));
            $this->assertInstanceOf(DataTablesLoadRequest::class, $dataTablesRequest);
            $this->assertEquals(1, $dataTablesRequest->draw);
            $this->assertEquals(0, $dataTablesRequest->start);
            $this->assertEquals(25, $dataTablesRequest->length);
        } catch (\Exception $e) {
            $this->fail('Deserialization failed: '.$e->getMessage());
        }
    }

    public function testValinorDeserializationWithEmptyValues()
    {
        $jsonInput = '{
            "draw": 1,
            "start": 0,
            "length": 25,
            "search": {"value": "", "regex": false},
            "order": [],
            "columns": []
        }';

        $mapper = (new MapperBuilder())->mapper();
        
        try {
            $dataTablesRequest = $mapper->map(DataTablesLoadRequest::class, json_decode($jsonInput, true));
            $this->assertInstanceOf(DataTablesLoadRequest::class, $dataTablesRequest);
        } catch (\Exception $e) {
            $this->fail('Empty values deserialization failed: '.$e->getMessage());
        }
    }
}