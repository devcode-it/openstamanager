<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'create:admin-user';

    protected $description = 'Create an admin user';

    public function handle(): void
    {
        do {
            $name = $this->ask('Name');
        } while (empty($name));

        do {
            $email = $this->ask('Email');
        } while (empty($email));

        do {
            $password = $this->secret('Password');
        } while (empty($password));

        $user = new User();
        $user->email = $email;
        $user->password = $password;
        $user->email_verified_at = now();
        $user->remember_token = Str::random(10);
        $user->save();

        $this->info('Admin user created successfully');
    }
}
