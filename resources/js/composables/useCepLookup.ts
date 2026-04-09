import { ref } from 'vue';

interface ViaCepResponse {
    cep: string;
    logradouro: string;
    complemento: string;
    bairro: string;
    localidade: string;
    uf: string;
    ibge: string;
    gia: string;
    ddd: string;
    siafi: string;
    erro?: boolean;
}

export function useCepLookup() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    let debounceTimer: ReturnType<typeof setTimeout> | null = null;

    const lookup = async (
        cepRaw: string,
        onSuccess: (data: {
            street: string;
            neighborhood: string;
            city: string;
            state: string;
        }) => void,
    ) => {
        const cep = cepRaw.replace(/\D/g, '');

        if (cep.length !== 8) {
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
                const response = await fetch(
                    `https://viacep.com.br/ws/${cep}/json/`,
                );
                if (!response.ok) {
                    throw new Error('Erro na requisição');
                }
                const data: ViaCepResponse = await response.json();
                if (data.erro) {
                    error.value = 'CEP não encontrado';
                } else {
                    onSuccess({
                        street: data.logradouro,
                        neighborhood: data.bairro,
                        city: data.localidade,
                        state: data.uf,
                    });
                }
            } catch {
                error.value = 'Erro ao consultar CEP';
            } finally {
                isLoading.value = false;
            }
        }, 400);
    };

    return {
        isLoading,
        error,
        lookup,
    };
}
