<?php

declare(strict_types=1);

namespace Tests\Models;

use Models\Module;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Auth;

class ModulePermissionTest extends TestCase
{
    public function testModulePermissionWithPivot()
    {
        // Test con pivot già caricato
        $module = Module::factory()->create();
        $group = \Models\Group::factory()->create();
        
        $module->groups()->attach($group->id, ['permessi' => 'rw']);
        $pivot = $module->groups()->first()->pivot;
        
        $module->pivot = $pivot;
        
        $this->assertEquals('rw', $module->permission, 
            'Permission should work with pre-loaded pivot');
    }

    public function testModulePermissionWithoutPivot()
    {
        // Test senza pivot caricato
        $module = Module::factory()->create();
        $group = \Models\Group::factory()->create();
        $user = \Models\User::factory()->create(['idgruppo' => $group->id]);
        
        $module->groups()->attach($group->id, ['permessi' => 'r']);
        
        Auth::shouldReceive('user')->andReturn($user);
        
        $permission = $module->permission;
        $this->assertEquals('r', $permission, 
            'Permission should work without pre-loaded pivot');
    }

    public function testModulePermissionWithNoPermission()
    {
        // Test senza permessi
        $module = Module::factory()->create();
        $group = \Models\Group::factory()->create();
        $user = \Models\User::factory()->create(['idgruppo' => $group->id]);
        
        // Non assegniamo permessi
        Auth::shouldReceive('user')->andReturn($user);
        
        $permission = $module->permission;
        $this->assertEquals('-', $permission, 
            'Permission should return default when no permissions assigned');
    }

    public function testModulePermissionForAdmin()
    {
        // Test per admin
        $module = Module::factory()->create();
        $user = \Models\User::factory()->create(['is_admin' => true]);
        
        Auth::shouldReceive('user')->andReturn($user);
        
        $permission = $module->permission;
        $this->assertEquals('rw', $permission, 
            'Admin should always have rw permission');
    }
}