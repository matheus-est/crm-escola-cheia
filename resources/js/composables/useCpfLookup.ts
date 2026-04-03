import { ref } from 'vue';
import { lookup as guardiansLookup } from '@/routes/tenant/guardians';
import { lookup as studentsLookup } from '@/routes/tenant/students';
import type { Guardian, Student } from '@/types/crm';

interface UseCpfLookupOptions {
    type: 'student' | 'guardian';
    schoolUuid: string;
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

        if (debounceTimer !== null) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(async () => {
            isLoading.value = true;
            error.value = null;

            try {
                const url =
                    options.type === 'student'
                        ? studentsLookup({
                              school_uuid: options.schoolUuid,
                              cpf: cpfValue,
                          }).url
                        : guardiansLookup({
                              school_uuid: options.schoolUuid,
                              cpf: cpfValue,
                          }).url;

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });

                if (response.status === 404) {
                    options.onNotFound?.();
                    error.value = null;
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
