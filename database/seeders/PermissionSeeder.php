<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleAction;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    protected array $data = [
        'dashboard' => [
            ['name' => 'view', 'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar dashboard'],
        ],
        'users' => [
            ['name' => 'list',         'label' => 'Listar',                          'order' => 1, 'description' => 'Listar usuários'],
            ['name' => 'add',          'label' => 'Cadastrar',                       'order' => 2, 'description' => 'Cadastrar usuário'],
            ['name' => 'edit',         'label' => 'Editar',                          'order' => 3, 'description' => 'Editar usuário'],
            ['name' => 'view',         'label' => 'Visualizar',                      'order' => 4, 'description' => 'Visualizar usuário'],
            ['name' => 'delete',       'label' => 'Excluir',                         'order' => 5, 'description' => 'Excluir usuário'],
            ['name' => 'restore',      'label' => 'Restaurar',                       'order' => 6, 'description' => 'Reativar usuário deletado'],
            ['name' => 'audit_login',  'label' => 'Histórico de Login',              'order' => 7, 'description' => 'Visualizar histórico de login do usuário'],
            ['name' => 'export_data',  'label' => 'Exportar dados do usuário (LGPD)', 'order' => 8, 'description' => 'Exportar dados do usuário (LGPD)'],
        ],
        'roles' => [
            ['name' => 'list',   'label' => 'Listar',      'order' => 1, 'description' => 'Listar perfis'],
            ['name' => 'add',    'label' => 'Cadastrar',   'order' => 2, 'description' => 'Cadastrar perfil'],
            ['name' => 'edit',   'label' => 'Editar',      'order' => 3, 'description' => 'Editar perfil'],
            ['name' => 'view',   'label' => 'Visualizar',  'order' => 4, 'description' => 'Visualizar perfil'],
            ['name' => 'delete', 'label' => 'Excluir',     'order' => 5, 'description' => 'Excluir perfil'],
        ],
        'permissions' => [
            ['name' => 'edit', 'label' => 'Editar', 'order' => 1, 'description' => 'Editar permissões'],
        ],
        'modules' => [
            ['name' => 'list', 'label' => 'Listar', 'order' => 1, 'description' => 'Listar módulos'],
            ['name' => 'edit', 'label' => 'Editar', 'order' => 2, 'description' => 'Editar módulo'],
        ],
        'menu_groups' => [
            ['name' => 'list',   'label' => 'Listar',    'order' => 1, 'description' => 'Listar grupos de menu'],
            ['name' => 'add',    'label' => 'Cadastrar', 'order' => 2, 'description' => 'Criar grupo de menu'],
            ['name' => 'edit',   'label' => 'Editar',    'order' => 3, 'description' => 'Editar grupo de menu'],
            ['name' => 'delete', 'label' => 'Excluir',   'order' => 4, 'description' => 'Excluir grupo de menu'],
        ],
        'terms' => [
            ['name' => 'list',   'label' => 'Listar',      'order' => 1, 'description' => 'Listar Termos de Uso'],
            ['name' => 'add',    'label' => 'Cadastrar',   'order' => 2, 'description' => 'Criar Termos de Uso'],
            ['name' => 'edit',   'label' => 'Editar',      'order' => 3, 'description' => 'Editar Termos de Uso'],
            ['name' => 'view',   'label' => 'Visualizar',  'order' => 4, 'description' => 'Visualizar Termos de Uso'],
            ['name' => 'delete', 'label' => 'Excluir',     'order' => 5, 'description' => 'Excluir Termos de Uso'],
        ],
        'settings' => [
            ['name' => 'list',   'label' => 'Listar',      'order' => 1, 'description' => 'Listar configurações do sistema'],
            ['name' => 'manage', 'label' => 'Gerenciar',   'order' => 1, 'description' => 'Gerenciar configurações do sistema'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->data as $slug => $actions) {
            $module = Module::where('slug', $slug)->first();

            if (! $module) {
                $this->command->warn("Module [{$slug}] not found. Skipping.");

                continue;
            }

            foreach ($actions as $action) {
                ModuleAction::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'name' => $action['name'],
                    ],
                    [
                        'label' => $action['label'],
                        'order' => $action['order'],
                    ]
                );

                Permission::updateOrCreate(
                    ['name' => "{$module->slug}_{$action['name']}"],
                    [
                        'module_id' => $module->id,
                        'description' => $action['description'],
                    ]
                );
            }

            $this->command->info("Module [{$slug}]: {$module->actions()->count()} actions and permissions synced.");
        }
    }
}
