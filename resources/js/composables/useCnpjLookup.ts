import { ref } from 'vue';
import { cnpjLookup } from '@/routes/admin/schools';

export interface CnpjLookupResult {
    razao_social: string;
    logradouro: string;
    numero: string;
    complemento: string;
    bairro: string;
    municipio: string;
    uf: string;
    cep: string;
}

export function maskCnpj(value: string): string {
    const d = value.replace(/\D/g, '').slice(0, 14);
    if (d.length <= 2) return d;
    if (d.length <= 5) return `${d.slice(0, 2)}.${d.slice(2)}`;
    if (d.length <= 8) return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5)}`;
    if (d.length <= 12)
        return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5, 8)}/${d.slice(8)}`;
    return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5, 8)}/${d.slice(8, 12)}-${d.slice(12)}`;
}

export function useCnpjLookup() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    let debounceTimer: ReturnType<typeof setTimeout> | null = null;

    const lookup = (
        cnpjRaw: string,
        onSuccess: (data: CnpjLookupResult) => void,
    ) => {
        const cnpj = cnpjRaw.replace(/\D/g, '');

        if (cnpj.length !== 14) {
            error.value = null;
            return;
        }

        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(async () => {
            isLoading.value = true;
            error.value = null;
            try {
                const response = await fetch(cnpjLookup(cnpj).url, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    throw new Error('CNPJ não encontrado');
                }
                const data = (await response.json()) as CnpjLookupResult;
                onSuccess(data);
            } catch {
                error.value = 'CNPJ não encontrado ou inválido';
            } finally {
                isLoading.value = false;
            }
        }, 400);
    };

    return { isLoading, error, lookup };
}
