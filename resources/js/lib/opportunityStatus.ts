import type { OpportunityStatus } from '@/types/crm';

export const statusLabels: Record<OpportunityStatus, string> = {
    cadastro_inicial: 'Cadastro Inicial',
    agendamento: 'Agendamento',
    visita: 'Visita',
    matricula: 'Matrícula',
    recusado: 'Recusado',
};

export const statusClasses: Record<OpportunityStatus, string> = {
    cadastro_inicial:
        'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20',
    agendamento:
        'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20',
    visita: 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/20',
    matricula:
        'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20',
    recusado:
        'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20',
};

export const terminalStatuses = ['matricula', 'recusado'] as const;

export function isTerminalStatus(status: string): boolean {
    return terminalStatuses.includes(
        status as (typeof terminalStatuses)[number],
    );
}
