<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Outcome;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OutcomeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->outcomes() as $data) {
                $outcome = Outcome::firstOrCreate(
                    ['slug' => $data['slug']],
                    ['uuid' => (string) Str::uuid()],
                );

                $outcome->update([
                    'name' => $data['name'],
                    'task_type' => $data['task_type'],
                    'is_refusal' => $data['is_refusal'] ?? false,
                    'opens_window' => $data['opens_window'] ?? null,
                ]);

                $outcome->actions()->delete();

                foreach ($data['actions'] as $i => $action) {
                    $outcome->actions()->create([
                        'uuid' => (string) Str::uuid(),
                        'action_type' => $action['type'],
                        'payload' => $action['payload'] ?? null,
                        'order' => $i,
                    ]);
                }
            }
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function outcomes(): array
    {
        return [
            // ---------------------------------------------------------------
            // Retorno de Ligação
            // ---------------------------------------------------------------
            [
                'slug' => 'retorno_ligacao_renitente',
                'name' => 'Renitente',
                'task_type' => 'retorno_ligacao',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'retorno_ligacao', 'renitente' => true]],
                ],
            ],
            [
                'slug' => 'retorno_ligacao_agendamento',
                'name' => 'Agendamento',
                'task_type' => 'retorno_ligacao',
                'opens_window' => 'agendamento',
                'actions' => [
                    ['type' => 'open_window', 'payload' => ['window' => 'agendamento']],
                ],
            ],
            [
                'slug' => 'retorno_ligacao_novo_retorno',
                'name' => 'Novo Retorno de Ligação',
                'task_type' => 'retorno_ligacao',
                'opens_window' => 'retorno_ligacao',
                'actions' => [
                    ['type' => 'open_window', 'payload' => ['window' => 'retorno_ligacao']],
                ],
            ],

            // ---------------------------------------------------------------
            // Agendamento
            // ---------------------------------------------------------------
            [
                'slug' => 'agendamento_compareceu',
                'name' => 'Compareceu Agendamento',
                'task_type' => 'agendamento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'visita']],
                    ['type' => 'create_task', 'payload' => ['task_type' => 'provavel_matricula']],
                ],
            ],
            [
                'slug' => 'agendamento_nao_compareceu',
                'name' => 'Não Compareceu Agendamento',
                'task_type' => 'agendamento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'reagendamento', 'delay_days' => 2]],
                ],
            ],

            // ---------------------------------------------------------------
            // Lembrete de Agenda
            // ---------------------------------------------------------------
            [
                'slug' => 'lembrete_agenda_vai_comparecer',
                'name' => 'Vai Comparecer Agenda',
                'task_type' => 'lembrete_agenda',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'lembrete_agenda']],
                ],
            ],
            [
                'slug' => 'lembrete_agenda_nao_vai_reagendou',
                'name' => 'Não Vai Comparecer — Reagendou',
                'task_type' => 'lembrete_agenda',
                'opens_window' => 'agendamento',
                'actions' => [
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'lembrete_agenda']],
                    ['type' => 'open_window', 'payload' => ['window' => 'agendamento']],
                ],
            ],
            [
                'slug' => 'lembrete_agenda_renitente',
                'name' => 'Renitente',
                'task_type' => 'lembrete_agenda',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'lembrete_agenda', 'renitente' => true]],
                ],
            ],

            // ---------------------------------------------------------------
            // Reagendamento
            // ---------------------------------------------------------------
            [
                'slug' => 'reagendamento_reagendado',
                'name' => 'Reagendado',
                'task_type' => 'reagendamento',
                'opens_window' => 'agendamento',
                'actions' => [
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'agendamento']],
                    ['type' => 'open_window', 'payload' => ['window' => 'agendamento']],
                ],
            ],
            [
                'slug' => 'reagendamento_visita_realizada',
                'name' => 'Visita Realizada',
                'task_type' => 'reagendamento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'visita']],
                    ['type' => 'create_task', 'payload' => ['task_type' => 'provavel_matricula']],
                ],
            ],
            [
                'slug' => 'reagendamento_novo_reagendamento',
                'name' => 'Novo Reagendamento',
                'task_type' => 'reagendamento',
                'opens_window' => 'reagendamento',
                'actions' => [
                    ['type' => 'open_window', 'payload' => ['window' => 'reagendamento']],
                ],
            ],
            [
                'slug' => 'reagendamento_renitente',
                'name' => 'Renitente',
                'task_type' => 'reagendamento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'reagendamento', 'renitente' => true]],
                ],
            ],

            // ---------------------------------------------------------------
            // Double Check
            // ---------------------------------------------------------------
            [
                'slug' => 'double_check_visitou',
                'name' => 'Visitou',
                'task_type' => 'double_check',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'visita']],
                    ['type' => 'create_task', 'payload' => ['task_type' => 'provavel_matricula']],
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'agendamento']],
                ],
            ],
            [
                'slug' => 'double_check_nao_visitou',
                'name' => 'Não Visitou',
                'task_type' => 'double_check',
                'opens_window' => 'agendamento',
                'actions' => [
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'agendamento']],
                    ['type' => 'open_window', 'payload' => ['window' => 'agendamento']],
                ],
            ],
            [
                'slug' => 'double_check_novo',
                'name' => 'Novo Double Check',
                'task_type' => 'double_check',
                'opens_window' => 'double_check',
                'actions' => [
                    ['type' => 'open_window', 'payload' => ['window' => 'double_check']],
                ],
            ],
            [
                'slug' => 'double_check_renitente',
                'name' => 'Renitente',
                'task_type' => 'double_check',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'double_check', 'renitente' => true]],
                ],
            ],

            // ---------------------------------------------------------------
            // Provável Matrícula
            // ---------------------------------------------------------------
            [
                'slug' => 'provavel_matricula_pre_matricula',
                'name' => 'Pré-Matrícula',
                'task_type' => 'provavel_matricula',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'matricula']],
                ],
            ],
            [
                'slug' => 'provavel_matricula_em_andamento',
                'name' => 'Em Andamento com Data Futura',
                'task_type' => 'provavel_matricula',
                'opens_window' => 'provavel_matricula',
                'actions' => [
                    ['type' => 'open_window', 'payload' => ['window' => 'provavel_matricula']],
                ],
            ],
            [
                'slug' => 'provavel_matricula_provavel',
                'name' => 'Provável',
                'task_type' => 'provavel_matricula',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'provavel_matricula', 'delay_days' => 4]],
                ],
            ],

            // ---------------------------------------------------------------
            // Evento
            // ---------------------------------------------------------------
            [
                'slug' => 'evento_compareceu',
                'name' => 'Compareceu Evento',
                'task_type' => 'evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'visita']],
                    ['type' => 'create_task', 'payload' => ['task_type' => 'provavel_matricula']],
                ],
            ],
            [
                'slug' => 'evento_nao_compareceu',
                'name' => 'Não Compareceu Evento',
                'task_type' => 'evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'reagendamento_evento', 'delay_days' => 2]],
                ],
            ],
            [
                'slug' => 'evento_nao_compareceu_sem_data',
                'name' => 'Não Compareceu sem Data',
                'task_type' => 'evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'evento']],
                ],
            ],

            // ---------------------------------------------------------------
            // Lembrete de Evento
            // ---------------------------------------------------------------
            [
                'slug' => 'lembrete_evento_vai_comparecer',
                'name' => 'Vai Comparecer Evento',
                'task_type' => 'lembrete_evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'lembrete_evento']],
                ],
            ],
            [
                'slug' => 'lembrete_evento_nao_vai_reagendou',
                'name' => 'Não Vai Comparecer — Reagendou',
                'task_type' => 'lembrete_evento',
                'opens_window' => 'evento',
                'actions' => [
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'lembrete_evento']],
                    ['type' => 'open_window', 'payload' => ['window' => 'evento']],
                ],
            ],
            [
                'slug' => 'lembrete_evento_renitente',
                'name' => 'Renitente',
                'task_type' => 'lembrete_evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'lembrete_evento', 'renitente' => true]],
                ],
            ],

            // ---------------------------------------------------------------
            // Reagendamento de Evento
            // ---------------------------------------------------------------
            [
                'slug' => 'reagendamento_evento_reagendado',
                'name' => 'Reagendado Evento',
                'task_type' => 'reagendamento_evento',
                'opens_window' => 'evento',
                'actions' => [
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'evento']],
                    ['type' => 'open_window', 'payload' => ['window' => 'evento']],
                ],
            ],
            [
                'slug' => 'reagendamento_evento_compareceu',
                'name' => 'Compareceu no Evento',
                'task_type' => 'reagendamento_evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'visita']],
                    ['type' => 'create_task', 'payload' => ['task_type' => 'provavel_matricula']],
                ],
            ],
            [
                'slug' => 'reagendamento_evento_renitente',
                'name' => 'Renitente',
                'task_type' => 'reagendamento_evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'reagendamento_evento', 'renitente' => true]],
                ],
            ],

            // ---------------------------------------------------------------
            // Double Check de Evento
            // ---------------------------------------------------------------
            [
                'slug' => 'double_check_evento_visitou',
                'name' => 'Visitou Evento',
                'task_type' => 'double_check_evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'visita']],
                    ['type' => 'create_task', 'payload' => ['task_type' => 'provavel_matricula']],
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'evento']],
                ],
            ],
            [
                'slug' => 'double_check_evento_nao_visitou',
                'name' => 'Não Visitou Evento',
                'task_type' => 'double_check_evento',
                'opens_window' => 'evento',
                'actions' => [
                    ['type' => 'cancel_tasks', 'payload' => ['task_type' => 'evento']],
                    ['type' => 'open_window', 'payload' => ['window' => 'evento']],
                ],
            ],
            [
                'slug' => 'double_check_evento_renitente',
                'name' => 'Renitente',
                'task_type' => 'double_check_evento',
                'opens_window' => null,
                'actions' => [
                    ['type' => 'create_task', 'payload' => ['task_type' => 'double_check_evento', 'renitente' => true]],
                ],
            ],

            // ---------------------------------------------------------------
            // Recusas (1 por task_type, is_refusal = true)
            // ---------------------------------------------------------------
            [
                'slug' => 'recusa_retorno_ligacao',
                'name' => 'Recusa',
                'task_type' => 'retorno_ligacao',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_agendamento',
                'name' => 'Recusa',
                'task_type' => 'agendamento',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_lembrete_agenda',
                'name' => 'Recusa',
                'task_type' => 'lembrete_agenda',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_reagendamento',
                'name' => 'Recusa',
                'task_type' => 'reagendamento',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_double_check',
                'name' => 'Recusa',
                'task_type' => 'double_check',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_provavel_matricula',
                'name' => 'Recusa',
                'task_type' => 'provavel_matricula',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_evento',
                'name' => 'Recusa',
                'task_type' => 'evento',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_lembrete_evento',
                'name' => 'Recusa',
                'task_type' => 'lembrete_evento',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_reagendamento_evento',
                'name' => 'Recusa',
                'task_type' => 'reagendamento_evento',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
            [
                'slug' => 'recusa_double_check_evento',
                'name' => 'Recusa',
                'task_type' => 'double_check_evento',
                'is_refusal' => true,
                'actions' => [
                    ['type' => 'move_status', 'payload' => ['status' => 'recusado']],
                ],
            ],
        ];
    }
}
