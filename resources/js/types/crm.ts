export type SchoolStatus = 'active' | 'inactive';

export interface Student {
    uuid: string;
    nome: string;
    cpf: string;
    data_nascimento: string | null;
    created_at: string;
    updated_at: string;
}

export interface Guardian {
    uuid: string;
    nome: string;
    cpf: string;
    telefone: string | null;
    email: string | null;
    cep: string | null;
    logradouro: string | null;
    numero: string | null;
    estado: string | null;
    cidade: string | null;
    bairro: string | null;
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
    school_id: string;
    nome: string;
    cep: string | null;
    logradouro: string | null;
    numero: string | null;
    complemento: string | null;
    bairro: string | null;
    cidade: string | null;
    estado: string | null;
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
    razao_social: string;
    nome_fantasia: string | null;
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
    nome: string;
    cep: string;
    logradouro: string;
    numero: string;
    complemento: string;
    bairro: string;
    cidade: string;
    estado: string;
}

export type SchoolYearStatus = 'ativo' | 'encerrado' | 'planejamento';

export interface SchoolYear {
    id: number;
    uuid: string;
    nome: string;
    inicio: string;
    fim: string;
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
    nome: string;
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
    nome: string;
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
    segment?: Segment | null;
    student?: Student | null;
    guardian?: Guardian | null;
    grade?: Grade | null;
    school_year?: SchoolYear | null;
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

export interface Task {
    uuid: string;
    type: TaskType;
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
