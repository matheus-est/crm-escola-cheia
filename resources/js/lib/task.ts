import type { TaskType } from '@/types/crm';

export const taskTypeLabels: Record<TaskType, string> = {
    retorno_ligacao: 'Retorno de Ligação',
    agendamento: 'Agendamento',
    lembrete_agenda: 'Lembrete de Agenda',
    reagendamento: 'Reagendamento',
    double_check: 'Double Check',
    provavel_matricula: 'Provável Matrícula',
    evento: 'Evento',
    lembrete_evento: 'Lembrete de Evento',
    reagendamento_evento: 'Reagendamento de Evento',
    double_check_evento: 'Double Check Evento',
};

export const refusalCategoryLabels: Record<string, string> = {
    fatores_externos: 'Fatores Externos',
    fatores_internos: 'Fatores Internos',
    pedagogicos: 'Pedagógicos',
    administrativos: 'Administrativos',
};
