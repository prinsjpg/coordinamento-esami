<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Crea ruoli, permessi e utenti di test.
     */
    public function run(): void
    {
        // Svuota la cache dei permessi prima di ricreare tutto
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Permessi dell'applicazione
        $permessi = [
            'appello_create',
            'appello_edit',
            'appello_delete',
            'struttura_manage',
            'config_manage',
        ];

        foreach ($permessi as $permesso) {
            Permission::firstOrCreate(['name' => $permesso]);
        }

        // Ruolo amministratore: tutti i permessi
        $admin = Role::firstOrCreate(['name' => 'amministratore']);
        $admin->syncPermissions($permessi);

        // Ruolo docente: solo gestione dei propri appelli
        $docente = Role::firstOrCreate(['name' => 'docente']);
        $docente->syncPermissions([
            'appello_create',
            'appello_edit',
            'appello_delete',
        ]);

        // Utenti di test: 1 amministratore e 2 docenti
        $amministratore = User::firstOrCreate(
            ['email' => 'admin@esami.test'],
            [
                'name' => 'Amministratore',
                'password' => Hash::make('password'),
            ]
        );
        $amministratore->assignRole('amministratore');

        foreach ([1, 2] as $i) {
            $user = User::firstOrCreate(
                ['email' => "docente{$i}@esami.test"],
                [
                    'name' => "Docente {$i}",
                    'password' => Hash::make('password'),
                ]
            );
            $user->assignRole('docente');
        }
    }
}
