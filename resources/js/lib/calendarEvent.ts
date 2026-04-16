// Color classes per sub_type (Tailwind bg + text)
export function entryColorClass(subType: string): string {
    switch (subType) {
        case 'agendamento':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'lembrete_agenda':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'lembrete_evento':
            return 'bg-teal-100 text-teal-800 border-teal-200';
        case 'event':
            return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'retorno_ligacao':
            return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'reagendamento':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'double_check':
            return 'bg-indigo-100 text-indigo-800 border-indigo-200';
        case 'provavel_matricula':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'reagendamento_evento':
            return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'double_check_evento':
            return 'bg-violet-100 text-violet-800 border-violet-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}

// Label per sub_type
export function entryLabel(subType: string): string {
    switch (subType) {
        case 'agendamento':
            return 'Agendamento';
        case 'lembrete_agenda':
            return 'Lembrete Agenda';
        case 'lembrete_evento':
            return 'Lembrete Evento';
        case 'event':
            return 'Evento';
        case 'retorno_ligacao':
            return 'Retorno de Ligação';
        case 'reagendamento':
            return 'Reagendamento';
        case 'double_check':
            return 'Double Check';
        case 'provavel_matricula':
            return 'Provável Matrícula';
        case 'reagendamento_evento':
            return 'Reagendamento de Evento';
        case 'double_check_evento':
            return 'Double Check Evento';
        default:
            return subType;
    }
}

// Returns days of a given month as Date objects (Sun–Sat grid, with leading/trailing days)
export function getMonthGrid(year: number, month: number): Date[] {
    // month is 0-indexed
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Adjust so week starts on Sunday (0) — pad leading days
    const startPad = firstDay.getDay(); // 0=Sun, 1=Mon, ...
    const endPad = 6 - lastDay.getDay();

    const days: Date[] = [];

    for (let i = startPad; i > 0; i--) {
        days.push(new Date(year, month, 1 - i));
    }
    for (let d = 1; d <= lastDay.getDate(); d++) {
        days.push(new Date(year, month, d));
    }
    for (let i = 1; i <= endPad; i++) {
        days.push(new Date(year, month + 1, i));
    }

    return days;
}

// Returns the 7 days of the week containing the given date (Sun–Sat)
export function getWeekDays(date: Date): Date[] {
    const start = new Date(date);
    start.setDate(date.getDate() - date.getDay());
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        return d;
    });
}

// Format date as YYYY-MM-DD HH:mm:ss for API params
export function toApiDateTime(date: Date): string {
    return date.toISOString().replace('T', ' ').substring(0, 19);
}
