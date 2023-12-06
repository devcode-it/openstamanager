<?php

namespace App\Console\Commands;

use App\Actions\Fortify\CreateNewUser;
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
        $password_confirmation = password('Confirm Password', required: true);

        $this->table(['Username', 'Email', 'Password'], [[$username, $email, '********']]);

        if (confirm('Are you sure you want to create this user?')) {
            $user = app(CreateNewUser::class)->create(compact('username', 'email', 'password', 'password_confirmation'));
            $user->email_verified_at = now();
            $user->save();

            $this->info('Admin user created successfully');
        } else {
            $this->info('Admin user creation aborted');
        }
    }
}
