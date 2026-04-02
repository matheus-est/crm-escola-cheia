<?php

declare(strict_types=1);

namespace App\Services\School;

use Illuminate\Support\Facades\Http;

class ViaCepService
{
    public function lookup(string $cep): array
    {
        $cep = preg_replace('/\D/', '', $cep);

        $response = Http::get("https://viacep.com.br/ws/{$cep}/json/");

        if ($response->failed()) {
            throw new \RuntimeException('CEP não encontrado ou API indisponível');
        }

        $data = $response->json();

        if (array_key_exists('erro', $data)) {
            throw new \RuntimeException('CEP não encontrado ou API indisponível');
        }

        return [
            'cep' => $data['cep'] ?? '',
            'logradouro' => $data['logradouro'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'cidade' => $data['localidade'] ?? '',
            'estado' => $data['uf'] ?? '',
        ];
    }
}
