import { ref } from 'vue';
import { lookup as guardiansLookup } from '@/routes/tenant/guardians';
import { lookup as studentsLookup } from '@/routes/tenant/students';
import type { Guardian, Student } from '@/types/crm';

function isValidCpf(cpf: string): boolean {
    const d = cpf.replace(/\D/g, '');
    if (d.length !== 11 || /^(\d)\1+$/.test(d)) return false;
    let sum = 0;
    for (let i = 0; i < 9; i++) sum += parseInt(d[i]!) * (10 - i);
    let r = (sum * 10) % 11;
    if (r === 10 || r === 11) r = 0;
    if (r !== parseInt(d[9]!)) return false;
    sum = 0;
    for (let i = 0; i < 10; i++) sum += parseInt(d[i]!) * (11 - i);
    r = (sum * 10) % 11;
    if (r === 10 || r === 11) r = 0;
    return r === parseInt(d[10]!);
}

interface UseCpfLookupOptions {
    type: 'student' | 'guardian';
    onFound: (data: Student | Guardian) => void;
    onNotFound?: () => void;
}

export function useCpfLookup(options: UseCpfLookupOptions) {
    const cpf = ref('');
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    let debounceTimer: ReturnType<typeof setTimeout> | null = null;

    const triggerLookup = async (cpfValue: string) => {
        if (cpfValue.length !== 14) {
            return;
        }

        if (!isValidCpf(cpfValue)) {
            isLoading.value = false;
            error.value = 'CPF inválido';
            return;
        }

        if (debounceTimer !== null) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(async () => {
            isLoading.value = true;
            error.value = null;

            try {
                const url =
                    options.type === 'student'
                        ? studentsLookup(cpfValue).url
                        : guardiansLookup(cpfValue).url;

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });

                if (response.status === 404) {
                    options.onNotFound?.();
                    error.value = null;
                } else if (response.status === 409) {
                    const data = (await response.json()) as {
                        error: string;
                        message: string;
                    };
                    error.value = data.message;
                } else if (response.ok) {
                    const data: Student | Guardian = await response.json();
                    options.onFound(data);
                    error.value = null;
                } else {
                    error.value = 'Erro ao consultar CPF. Tente novamente.';
                }
            } catch {
                error.value = 'Erro ao consultar CPF. Tente novamente.';
            } finally {
                isLoading.value = false;
            }
        }, 400);
    };

    const reset = () => {
        if (debounceTimer !== null) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        cpf.value = '';
        isLoading.value = false;
        error.value = null;
    };

    return {
        cpf,
        isLoading,
        error,
        reset,
        triggerLookup,
    };
}
