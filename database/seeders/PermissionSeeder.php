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
            ['name' => 'manage', 'label' => 'Gerenciar',   'order' => 2, 'description' => 'Gerenciar configurações do sistema'],
        ],
        'schools' => [
            ['name' => 'view',   'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar escola'],
            ['name' => 'list',   'label' => 'Listar',     'order' => 2, 'description' => 'Listar escolas'],
            ['name' => 'add',    'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar escola'],
            ['name' => 'edit',   'label' => 'Editar',     'order' => 4, 'description' => 'Editar escola'],
            ['name' => 'delete', 'label' => 'Excluir',    'order' => 5, 'description' => 'Remover escola'],
        ],

        'opportunities' => [
            ['name' => 'view',   'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar oportunidade'],
            ['name' => 'list',   'label' => 'Listar',     'order' => 2, 'description' => 'Listar oportunidades'],
            ['name' => 'add',    'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar oportunidade'],
            ['name' => 'edit',   'label' => 'Editar',     'order' => 4, 'description' => 'Editar oportunidade'],
            ['name' => 'delete', 'label' => 'Excluir',    'order' => 5, 'description' => 'Remover oportunidade'],
        ],

        'tasks' => [
            ['name' => 'view',     'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar tarefa'],
            ['name' => 'list',     'label' => 'Listar',     'order' => 2, 'description' => 'Listar tarefas'],
            ['name' => 'add',      'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar tarefa'],
            ['name' => 'edit',     'label' => 'Editar',     'order' => 4, 'description' => 'Editar tarefa'],
            ['name' => 'complete', 'label' => 'Concluir',   'order' => 5, 'description' => 'Marcar tarefa como concluída'],
        ],

        'school_years' => [
            ['name' => 'view',   'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar ano letivo'],
            ['name' => 'list',   'label' => 'Listar',     'order' => 2, 'description' => 'Listar anos letivos'],
            ['name' => 'add',    'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar ano letivo'],
            ['name' => 'edit',   'label' => 'Editar',     'order' => 4, 'description' => 'Editar ano letivo'],
            ['name' => 'delete', 'label' => 'Excluir',    'order' => 5, 'description' => 'Remover ano letivo'],
        ],

        'lead_sources' => [
            ['name' => 'view',   'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar origem do lead'],
            ['name' => 'list',   'label' => 'Listar',     'order' => 2, 'description' => 'Listar origens de lead'],
            ['name' => 'add',    'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar origem de lead'],
            ['name' => 'edit',   'label' => 'Editar',     'order' => 4, 'description' => 'Editar origem de lead'],
            ['name' => 'delete', 'label' => 'Excluir',    'order' => 5, 'description' => 'Remover origem de lead'],
        ],

        'grades' => [
            ['name' => 'view',   'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar série'],
            ['name' => 'list',   'label' => 'Listar',     'order' => 2, 'description' => 'Listar séries'],
            ['name' => 'add',    'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar série'],
            ['name' => 'edit',   'label' => 'Editar',     'order' => 4, 'description' => 'Editar série'],
            ['name' => 'delete', 'label' => 'Excluir',    'order' => 5, 'description' => 'Remover série'],
        ],

        'events' => [
            ['name' => 'view',   'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar evento'],
            ['name' => 'list',   'label' => 'Listar',     'order' => 2, 'description' => 'Listar eventos'],
            ['name' => 'add',    'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar evento'],
            ['name' => 'edit',   'label' => 'Editar',     'order' => 4, 'description' => 'Editar evento'],
            ['name' => 'delete', 'label' => 'Excluir',    'order' => 5, 'description' => 'Remover evento'],
        ],

        'event_types' => [
            ['name' => 'view',    'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar tipo de evento'],
            ['name' => 'list',    'label' => 'Listar',     'order' => 2, 'description' => 'Listar tipos de evento'],
            ['name' => 'add',     'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar tipo de evento'],
            ['name' => 'edit',    'label' => 'Editar',     'order' => 4, 'description' => 'Editar tipo de evento'],
            ['name' => 'delete',  'label' => 'Excluir',    'order' => 5, 'description' => 'Inativar tipo de evento'],
            ['name' => 'restore', 'label' => 'Reativar',   'order' => 6, 'description' => 'Reativar tipo de evento'],
        ],

        'calendar' => [
            ['name' => 'list', 'label' => 'Listar', 'order' => 1, 'description' => 'Listar entradas da agenda'],
            ['name' => 'view', 'label' => 'Visualizar', 'order' => 2, 'description' => 'Visualizar agenda'],
        ],

        'rooms' => [
            ['name' => 'view',   'label' => 'Visualizar', 'order' => 1, 'description' => 'Visualizar sala'],
            ['name' => 'list',   'label' => 'Listar',     'order' => 2, 'description' => 'Listar salas'],
            ['name' => 'add',    'label' => 'Adicionar',  'order' => 3, 'description' => 'Cadastrar sala'],
            ['name' => 'edit',   'label' => 'Editar',     'order' => 4, 'description' => 'Editar sala'],
            ['name' => 'delete', 'label' => 'Excluir',    'order' => 5, 'description' => 'Remover sala'],
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
