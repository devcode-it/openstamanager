<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'create:admin-user';

    protected $description = 'Create an admin user';

    public function handle(): void
    {
        $username = text('Username', required: true);
        $email = text('Email', required: true);
        $password = password('Password', required: true);

        $this->table(['Username', 'Email', 'Password'], [[$username, $email, '********']]);

        if (confirm('Are you sure you want to create this user?')) {
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->password = $password;
            $user->email_verified_at = now();
            $user->remember_token = Str::random(10);
            $user->save();

            $this->info('Admin user created successfully');
        } else {
            $this->info('Admin user creation aborted');
        }
    }
}
