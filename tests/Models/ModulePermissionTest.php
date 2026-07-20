<?php

declare(strict_types=1);

namespace Tests\Models;

use Models\Module;
use Models\Group;
use Models\User;
use PHPUnit\Framework\TestCase;

class ModulePermissionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        include_once __DIR__ . '/../../core.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $database = database();
        $database->beginTransaction();
    }

    protected function tearDown(): void
    {
        $database = database();
        $database->rollbackTransaction();

        parent::tearDown();
    }

    public function testModulePermissionWithPivot()
    {
        $database = database();

        $module = new Module();
        $module->name = 'Test Module Permission Pivot';
        $module->directory = 'test_module_perm_pivot';
        $module->options = '';
        $module->icon = 'fa fa-cogs';
        $module->version = '2.12';
        $module->compatibility = '2.*';
        $module->order = 1;
        $module->default = 1;
        $module->enabled = 1;
        $module->save();

        $id_module = $module->id;

        $group = new Group();
        $group->nome = 'Test Group Pivot';
        $group->save();

        $database->query('INSERT INTO zz_permissions (id_module, id_gruppo, permessi) VALUES (?, ?, ?)', [$id_module, $group->id, 'rw']);

        $module->load('groups');
        $pivot = $module->groups->first()->pivot;

        $module->pivot = $pivot;

        $this->assertEquals('rw', $module->permission,
            'Permission should work with pre-loaded pivot');
    }

    public function testModulePermissionWithoutPivot()
    {
        $database = database();

        $module = new Module();
        $module->name = 'Test Module Permission NoPivot';
        $module->directory = 'test_module_perm_nopivot';
        $module->options = '';
        $module->icon = 'fa fa-cogs';
        $module->version = '2.12';
        $module->compatibility = '2.*';
        $module->order = 1;
        $module->default = 1;
        $module->enabled = 1;
        $module->save();

        $id_module = $module->id;

        $group = new Group();
        $group->nome = 'Test Group NoPivot';
        $group->save();

        $user = new User();
        $user->username = 'test_user_nopivot';
        $user->password = 'password';
        $user->email = '[EMAIL]';
        $user->id_gruppo = $group->id;
        $user->enabled = 1;
        $user->save();

        $database->query('INSERT INTO zz_permissions (id_module, id_gruppo, permessi) VALUES (?, ?, ?)', [$id_module, $group->id, 'r']);

        $user->setRelation('group', $group);
        $this->setAuthUser($user);

        $module->load('groups');

        $permission = $module->permission;
        $this->assertEquals('r', $permission,
            'Permission should work without pre-loaded pivot');
    }

    public function testModulePermissionWithNoPermission()
    {
        $database = database();

        $module = new Module();
        $module->name = 'Test Module Permission None';
        $module->directory = 'test_module_perm_none';
        $module->options = '';
        $module->icon = 'fa fa-cogs';
        $module->version = '2.12';
        $module->compatibility = '2.*';
        $module->order = 1;
        $module->default = 1;
        $module->enabled = 1;
        $module->save();

        $group = new Group();
        $group->nome = 'Test Group None';
        $group->save();

        $user = new User();
        $user->username = 'test_user_none';
        $user->password = 'password';
        $user->email = '[EMAIL]';
        $user->id_gruppo = $group->id;
        $user->enabled = 1;
        $user->save();

        $user->setRelation('group', $group);
        $this->setAuthUser($user);

        $module->load('groups');

        $permission = $module->permission;
        $this->assertEquals('-', $permission,
            'Permission should return default when no permissions assigned');
    }

    public function testModulePermissionForAdmin()
    {
        $group = new Group();
        $group->nome = 'Amministratori';
        $group->save();

        $module = new Module();
        $module->name = 'Test Module Permission Admin';
        $module->directory = 'test_module_perm_admin';
        $module->options = '';
        $module->icon = 'fa fa-cogs';
        $module->version = '2.12';
        $module->compatibility = '2.*';
        $module->order = 1;
        $module->default = 1;
        $module->enabled = 1;
        $module->save();

        $user = new User();
        $user->username = 'admin_test_perm';
        $user->password = 'password';
        $user->email = '[EMAIL]';
        $user->id_gruppo = $group->id;
        $user->enabled = 1;
        $user->save();

        $user->setRelation('group', $group);
        $this->setAuthUser($user);

        $permission = $module->permission;
        $this->assertEquals('rw', $permission,
            'Admin should always have rw permission');
    }

    private function setAuthUser($user): void
    {
        $auth = auth_osm();
        $reflection = new \ReflectionProperty($auth, 'user');
        $reflection->setValue($auth, $user);
    }
}