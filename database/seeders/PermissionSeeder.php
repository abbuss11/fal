<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['code' => 'users.read', 'label' => 'Utilisateurs - lecture'],
            ['code' => 'users.create', 'label' => 'Utilisateurs - creation'],
            ['code' => 'users.update', 'label' => 'Utilisateurs - mise a jour'],
            ['code' => 'users.delete', 'label' => 'Utilisateurs - suppression'],
            ['code' => 'dashboard.read', 'label' => 'Dashboard - lecture'],
            ['code' => 'notifications.read', 'label' => 'Notifications - lecture'],
            ['code' => 'projects.read', 'label' => 'Projets - lecture'],
            ['code' => 'projects.create', 'label' => 'Projets - creation'],
            ['code' => 'projects.update', 'label' => 'Projets - mise a jour'],
            ['code' => 'projects.delete', 'label' => 'Projets - suppression'],
            ['code' => 'projects.archive', 'label' => 'Projets - archivage'],
            ['code' => 'projects.duplicate', 'label' => 'Projets - duplication'],
            ['code' => 'projects.templates.manage', 'label' => 'Projets - templates'],
            ['code' => 'projects.manage_members', 'label' => 'Projets - gestion des membres'],
            ['code' => 'tasks.read', 'label' => 'Taches - lecture'],
            ['code' => 'tasks.create', 'label' => 'Taches - creation'],
            ['code' => 'tasks.update', 'label' => 'Taches - mise a jour'],
            ['code' => 'tasks.delete', 'label' => 'Taches - suppression'],
            ['code' => 'tasks.move', 'label' => 'Taches - deplacement kanban'],
            ['code' => 'tasks.subtasks.manage', 'label' => 'Sous-taches - gestion'],
            ['code' => 'tasks.dependencies.manage', 'label' => 'Taches - dependances'],
            ['code' => 'tasks.tags.manage', 'label' => 'Taches - tags'],
            ['code' => 'comments.read', 'label' => 'Commentaires - lecture'],
            ['code' => 'comments.create', 'label' => 'Commentaires - creation'],
            ['code' => 'messages.read', 'label' => 'Chat interne - lecture'],
            ['code' => 'messages.create', 'label' => 'Chat interne - envoi'],
            ['code' => 'files.read', 'label' => 'Fichiers - lecture'],
            ['code' => 'files.create', 'label' => 'Fichiers - upload'],
            ['code' => 'timesheets.read', 'label' => 'Timesheet - lecture'],
            ['code' => 'timesheets.create', 'label' => 'Timesheet - creation'],
            ['code' => 'timesheets.update', 'label' => 'Timesheet - mise a jour'],
            ['code' => 'clients.read', 'label' => 'Clients - lecture'],
            ['code' => 'clients.create', 'label' => 'Clients - creation'],
            ['code' => 'clients.update', 'label' => 'Clients - mise a jour'],
            ['code' => 'clients.delete', 'label' => 'Clients - suppression'],
            ['code' => 'notifications.preferences.manage', 'label' => 'Notifications - preferences'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission['code']],
                [
                    'label' => $permission['label'],
                    'description' => null,
                ],
            );
        }
    }
}
