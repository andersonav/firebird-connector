<?php

namespace AndersonAv\Firebird\Tests;

use AndersonAv\Firebird\Tests\Support\Factories\UserFactory;
use AndersonAv\Firebird\Tests\Support\MigrateDatabase;
use AndersonAv\Firebird\Tests\Support\Models\User;

class ModelTest extends TestCase
{
    use MigrateDatabase;

    /** @test */
    public function it_can_create_a_record()
    {
        User::create($fields = [
            'id' => $id = UserFactory::$id++, // Firebird < 3 does not support auto-incrementing columns.
            'name' => 'Anderson',
            'email' => 'anderson@example.com',
            'city' => 'Londres',
            'state' => 'New Orlens',
            'post_code' => '4000',
            'country' => 'EUA',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $user = User::find($id);

        $this->assertInstanceOf(User::class, $user);

        // Check all fields have been persisted the model.
        foreach ($fields as $key => $value) {
            $this->assertEquals($value, $user->{$key});
        }
    }
}
