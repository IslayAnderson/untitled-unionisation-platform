<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * An admin account that isn't a tenant of any union. The password is asked for, never passed as an
 * argument, so it stays out of the shell history.
 */
#[Signature('admin:create {email : The login email for the new account}')]
#[Description('Create an account with access to the platform admin area, not linked to any union')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (User::where('email', $email)->exists()) {
            $this->error("There's already an account with that email address. Use admin:grant {$email} to make it an admin.");

            return self::FAILURE;
        }

        $password = $this->secret('Password');
        $validator = Validator::make(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $this->secret('Confirm the password')],
            ['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'confirmed', Password::defaults()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::create(['email' => $email, 'password' => $password]);
        $user->forceFill(['email_verified_at' => now(), 'is_admin' => true])->save();

        $this->info("{$email} is now an admin. Log in at ".route('login').'.');

        return self::SUCCESS;
    }
}
