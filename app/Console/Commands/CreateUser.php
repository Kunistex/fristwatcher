<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'fristwatcher:create-user {email?} {--name=}';

    protected $description = 'Legt ein Benutzerkonto an';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('E-Mail');
        $name = $this->option('name') ?? $this->ask('Name');
        $password = $this->secret('Passwort (min. 10 Zeichen)');

        $validator = Validator::make(compact('email', 'name', 'password'), [
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:10'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $this->info("Konto für {$email} angelegt.");

        return self::SUCCESS;
    }
}
