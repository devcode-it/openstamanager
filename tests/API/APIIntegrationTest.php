<?php

declare(strict_types=1);

namespace Tests\API;

use PHPUnit\Framework\TestCase;

class APIIntegrationTest extends TestCase
{
    public function testBaseControllerExists()
    {
        $this->assertTrue(class_exists(\API\Controllers\BaseController::class), 
            'BaseController class should exist after PR merge');
    }

    public function testControllersExist()
    {
        $controllers = [
            \API\Controllers\DataTablesController::class,
            \Modules\Impostazioni\API\Controllers\GetImpostazioneController::class,
            \Modules\Impostazioni\API\Controllers\ListImpostazioniController::class,
            \Modules\Impostazioni\API\Controllers\UpdateImpostazioneController::class,
        ];

        foreach ($controllers as $controller) {
            $this->assertTrue(class_exists($controller), 
                "Controller {$controller} should exist");
        }
    }

    public function testProvidersNotExists()
    {
        $providers = [
            \Modules\Impostazioni\API\Controllers\GetImpostazioneProvider::class,
            \Modules\Impostazioni\API\Controllers\ListImpostazioniProvider::class,
            \Modules\Impostazioni\API\Controllers\UpdateImpostazioneProcessor::class,
        ];

        foreach ($providers as $provider) {
            $this->assertFalse(class_exists($provider), 
                "Provider {$provider} should not exist after PR merge");
        }
    }

    public function testValinorDependencyExists()
    {
        $this->assertTrue(class_exists(\CuyZ\Valinor\MapperBuilder::class), 
            'Valinor MapperBuilder should be available');
    }

    public function testImpostazioneResourceUpdated()
    {
        $reflection = new \ReflectionClass(\Modules\Impostazioni\API\ImpostazioneResource::class);
        $attributes = $reflection->getAttributes(\ApiPlatform\Metadata\ApiResource::class);
        
        $this->assertNotEmpty($attributes, 'ImpostazioneResource should have ApiResource attribute');
        
        $apiResourceAttribute = $attributes[0]->newInstance();
        $operationsProperty = new \ReflectionProperty($apiResourceAttribute, 'operations');
        $operations = $operationsProperty->getValue($apiResourceAttribute);
        
        $this->assertNotEmpty($operations, 'Resource should have operations');
        
        foreach ($operations as $operation) {
            $this->assertNotNull($operation->getController(), 
                'Operations should use controller instead of provider/processor');
        }
    }
}