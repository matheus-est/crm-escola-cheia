import { ref, type Ref } from 'vue';
import { cnpjLookup } from '@/routes/admin/schools';

export interface CnpjLookupResult {
    legal_name: string;
    trade_name: string;
    street: string;
    number: string;
    complement: string;
    neighborhood: string;
    city: string;
    state: string;
    zip_code: string;
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

export function isValidCnpj(cnpj: string): boolean {
    const d = cnpj.replace(/\D/g, '');
    if (d.length !== 14) return false;

    // Reject all-same-digit CNPJs
    if (/^(\d)\1{13}$/.test(d)) return false;

    const calcDigit = (base: string, weights: number[]): number => {
        let sum = 0;
        for (let i = 0; i < weights.length; i++) {
            sum += parseInt(base[i], 10) * weights[i];
        }
        const remainder = sum % 11;
        return remainder < 2 ? 0 : 11 - remainder;
    };

    const w1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    const w2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    const digit1 = calcDigit(d, w1);
    const digit2 = calcDigit(d, w2);

    return parseInt(d[12], 10) === digit1 && parseInt(d[13], 10) === digit2;
}

export function useCnpjLookup() {
    const isLoading: Ref<boolean> = ref(false);
    const error: Ref<string | null> = ref(null);
    const cnpjNotFound: Ref<boolean> = ref(false);

    let debounceTimer: ReturnType<typeof setTimeout> | null = null;

    const lookup = (
        cnpjRaw: string,
        onSuccess: (data: CnpjLookupResult) => void,
    ) => {
        const cnpj = cnpjRaw.replace(/\D/g, '');

        if (cnpj.length !== 14) {
            error.value = null;
            cnpjNotFound.value = false;
            return;
        }

        if (!isValidCnpj(cnpj)) {
            error.value = 'CNPJ inválido';
            cnpjNotFound.value = false;
            return;
        }

        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(async () => {
            isLoading.value = true;
            error.value = null;
            cnpjNotFound.value = false;
            try {
                const response = await fetch(cnpjLookup(cnpj).url, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    throw new Error('not_found');
                }
                const data = (await response.json()) as CnpjLookupResult;
                cnpjNotFound.value = false;
                onSuccess(data);
            } catch {
                // Valid CNPJ but API unavailable or returned error — do not block
                cnpjNotFound.value = true;
                error.value = null;
            } finally {
                isLoading.value = false;
            }
        }, 400);
    };

    return { isLoading, error, cnpjNotFound, lookup };
}
