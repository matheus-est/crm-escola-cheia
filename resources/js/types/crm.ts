export type SchoolStatus = 'active' | 'inactive';

export interface Student {
    uuid: string;
    name: string;
    cpf: string;
    birth_date?: string | null;
    created_at: string;
    updated_at: string;
    guardian?: Guardian | null;
}

export interface Guardian {
    uuid: string;
    name: string;
    cpf: string;
    phone: string | null;
    email: string | null;
    zip_code: string | null;
    street: string | null;
    number: string | null;
    state: string | null;
    city: string | null;
    neighborhood: string | null;
    complement: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedStudents {
    data: Student[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface PaginatedGuardians {
    data: Guardian[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface SchoolUnit {
    id: string;
    uuid: string;
    school_id: string;
    name: string;
    zip_code: string | null;
    street: string | null;
    number: string | null;
    complement: string | null;
    neighborhood: string | null;
    city: string | null;
    state: string | null;
    created_at: string;
    updated_at: string;
}

export interface SchoolUser {
    id: number;
    uuid: string;
    name: string;
    email: string;
    role: { id: number; uuid: string; name: string } | null;
    pivot: { is_active: boolean };
}

export interface School {
    id: string;
    uuid: string;
    cnpj: string;
    legal_name: string;
    trade_name: string | null;
    slug: string;
    logo_path: string | null;
    address_json: Record<string, string> | null;
    status: SchoolStatus;
    observations: string | null;
    unassigned_lead_alert_days: number | null;
    created_at: string;
    updated_at: string;
    units?: SchoolUnit[];
    users?: SchoolUser[];
}

export interface PaginatedSchools {
    data: School[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface SchoolUnitForm {
    name: string;
    zip_code: string;
    street: string;
    number: string;
    complement: string;
    neighborhood: string;
    city: string;
    state: string;
}

export type SchoolYearStatus = 'ativo' | 'encerrado' | 'planejamento';

export interface SchoolYear {
    id: number;
    uuid: string;
    name: string;
    start: string;
    end: string;
    status: SchoolYearStatus;
    created_at: string;
    updated_at: string;
}

export interface PaginatedSchoolYears {
    data: SchoolYear[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface LeadSource {
    id: number;
    uuid: string;
    name: string;
    is_system: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedLeadSources {
    data: LeadSource[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export type OpportunityStatus =
    | 'cadastro_inicial'
    | 'agendamento'
    | 'visita'
    | 'matricula'
    | 'recusado';

export type RegistrationType = 'agendamento' | 'evento';

export interface Grade {
    id: number;
    uuid: string;
    name: string;
    segment_id: number;
}

export interface Segment {
    id: number;
    uuid: string;
    name: string;
}

export interface Opportunity {
    uuid: string;
    status: OpportunityStatus;
    observations: string | null;
    history: string | null;
    indications: string | null;
    registration_type: RegistrationType | null;
    renitente_count: number;
    segment?: Segment | null;
    student?: Student | null;
    guardian?: Guardian | null;
    grade?: Grade | null;
    school_year?: SchoolYear | null;
    school_unit?: SchoolUnit | null;
    lead_source?: LeadSource | null;
    responsible_user?: { uuid: string; name: string } | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedOpportunities {
    data: Opportunity[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export type KanbanColumn = {
    data: Opportunity[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export type KanbanColumns = Record<string, KanbanColumn>;

export interface TenantUser {
    id: number;
    uuid: string;
    name: string;
    email: string;
}

export interface ResponsavelInput {
    _key: string;
    name: string;
    email: string;
    role_id: string;
}

export type TaskStatus = 'open' | 'completed' | 'cancelled';
export type TaskType =
    | 'retorno_ligacao'
    | 'agendamento'
    | 'lembrete_agenda'
    | 'reagendamento'
    | 'double_check'
    | 'provavel_matricula'
    | 'evento'
    | 'lembrete_evento'
    | 'reagendamento_evento'
    | 'double_check_evento';

export interface Outcome {
    uuid: string;
    name: string;
    slug: string;
    task_type: string;
    is_refusal: boolean;
    opens_window: string | null;
}

export interface FunnelStage {
    label: string;
    slug: string;
    state: 'completed' | 'active' | 'pending';
}

export interface Task {
    uuid: string;
    type: TaskType;
    task_type?: { name: string } | null;
    status: TaskStatus;
    is_schedule: boolean;
    notes: string | null;
    scheduled_at: string | null;
    due_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    opportunity?: {
        uuid: string;
        status: string;
        student: { name: string } | null;
    } | null;
    assigned_user?: { uuid: string; name: string } | null;
    outcome?: {
        uuid: string;
        name: string;
        is_refusal: boolean;
    } | null;
}

export interface PaginatedGrades {
    data: (Grade & { order: number; segment?: Segment | null })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface PaginatedTasks {
    data: Task[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface Event {
    uuid: string;
    title: string;
    event_type: EventType | null;
    has_no_date: boolean;
    event_date: string | null;
    location: string | null;
    max_capacity: number | null;
    grade_id: number | null;
    grade?: Grade | null;
    rooms?: Room[];
    opportunities_count?: number;
    opportunities?: Opportunity[];
    created_at: string;
    updated_at: string;
}

export interface PaginatedEvents {
    data: Event[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface Room {
    uuid: string;
    name: string;
    capacity: number | null;
    is_external: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedRooms {
    data: Room[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface AvailableOpportunity {
    uuid: string;
    created_at: string;
    guardian_name: string | null;
    student_name: string | null;
    status: OpportunityStatus;
    school_year_name: string | null;
    registration_type: RegistrationType | null;
}

export interface EventType {
    uuid: string;
    name: string;
    is_active: boolean;
}

export type PaginatedEventTypes = {
    data: EventType[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
