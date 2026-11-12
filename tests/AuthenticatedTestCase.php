<?php

namespace Tests;

use App\Models\User;

abstract class AuthenticatedTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(new User(['id' => 999999, 'name' => 'Administrador de pruebas', 'role' => 'superadmin', 'active' => true]));
    }
}
