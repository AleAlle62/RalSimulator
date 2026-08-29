<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * `is_admin` is left out of the model's fillable attributes on purpose, so it cannot be set
 * through a registration payload — and by the same token not through `update()` either. This
 * command is the one intended way in, and the one that works on a deployed server where
 * there is no tinker session to lean on.
 */
#[Signature('user:promote {email : Email address of the account to promote}')]
#[Description('Grant an existing user access to the Filament admin panel')]
class PromoteUserToAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("Nessun utente con l'indirizzo {$email}.");

            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->info("{$user->email} è già amministratore.");

            return self::SUCCESS;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("{$user->email} ora può accedere al pannello.");

        return self::SUCCESS;
    }
}
