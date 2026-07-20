<?php

declare(strict_types=1);

namespace Tests\API\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use DTO\DataTablesLoadRequest\DataTablesLoadRequest;
use DTO\DataTablesLoadRequest\Search;
use DTO\DataTablesLoadRequest\OrderItem;
use DTO\DataTablesLoadRequest\Column;

class DataTablesControllerTest extends TestCase
{
    public function testDataTablesControllerExtendsBaseController()
    {
        $controller = new \API\Controllers\DataTablesController();
        $this->assertInstanceOf(\API\Controllers\BaseController::class, $controller,
            'DataTablesController should extend BaseController');
    }

    public function testDataTablesControllerUsesControllerNotProcessor()
    {
        $reflection = new \ReflectionClass(\API\Controllers\DataTablesResource::class);
        $attributes = $reflection->getAttributes(\ApiPlatform\Metadata\Post::class);
        
        $this->assertNotEmpty($attributes, 'DataTablesResource should have Post attribute');
        
        $postAttribute = $attributes[0]->newInstance();
        $controllerProperty = new \ReflectionProperty($postAttribute, 'controller');
        $this->assertEquals(\API\Controllers\DataTablesController::class, $controllerProperty->getValue($postAttribute),
            'Should use controller instead of processor');
    }

    public function testDataTablesLoadRequestStructure()
    {
        $request = new DataTablesLoadRequest();
        $request->draw = 1;
        $request->start = 0;
        $request->length = 25;
        
        $this->assertEquals(1, $request->getDraw());
        $this->assertEquals(0, $request->getStart());
        $this->assertEquals(25, $request->getLength());
    }

    public function testDataTablesLoadRequestWithSearch()
    {
        $search = new Search('test', false);
        $request = new DataTablesLoadRequest();
        $request->search = $search;
        
        $this->assertSame($search, $request->getSearch());
    }

    public function testDataTablesLoadRequestWithOrder()
    {
        $order = [new OrderItem(0, null, \DTO\DataTablesLoadRequest\SortDirection::ASC)];
        $request = new DataTablesLoadRequest();
        $request->order = $order;
        
        $this->assertSame($order, $request->getOrder());
    }

    public function testDataTablesLoadRequestWithColumns()
    {
        $column = new Column('nome', null, true, true, null);
        $request = new DataTablesLoadRequest();
        $request->columns = [$column];
        
        $this->assertSame([$column], $request->getColumns());
    }

    public function testBaseControllerHasAccessMethods()
    {
        $baseController = $this->createStub(\API\Controllers\BaseController::class);
        
        $this->assertTrue(method_exists($baseController, 'hasModuleReadAccess'),
            'BaseController should have hasModuleReadAccess method');
        $this->assertTrue(method_exists($baseController, 'hasModuleWriteAccess'),
            'BaseController should have hasModuleWriteAccess method');
        $this->assertTrue(method_exists($baseController, 'init'),
            'BaseController should have init method');
    }
}