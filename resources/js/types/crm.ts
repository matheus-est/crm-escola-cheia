export type SchoolStatus = 'active' | 'inactive';

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
