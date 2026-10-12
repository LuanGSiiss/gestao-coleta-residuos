<?php

namespace App\Http\Requests;

use App\Models\Rota;
use App\Support\Trajeto;
use App\Rules\TrajetoGeoJson;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Foundation\Http\FormRequest;

class RotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identificacao' => trim((string) $this->input('identificacao')),
            'cor'           => mb_strtolower((string) $this->input('cor')),
        ]);
    }

    public function rules(): array
    {
        $rota = $this->route('rota');

        return [
            'identificacao' => [
                'required',
                'string',
                'max:20',
                'alpha_dash:ascii',
                Rule::unique('rota', 'identificacao')->ignore($rota?->id),
            ],
            'nome'      => ['required', 'string', 'max:120', Rule::unique('rota', 'nome')->ignore($rota?->id)],
            'descricao' => ['required', 'string', 'max:2000'],
            'cor'       => ['required', 'regex:/^#[0-9a-f]{6}$/'],
            'ativo'     => ['required', 'boolean'],

            'bairros'   => ['required', 'array', 'min:1'],
            'bairros.*' => ['integer', 'distinct', $this->regraBairro($rota)],

            'trajeto'   => ['required', 'string', 'max:5000000', new TrajetoGeoJson()],
        ];
    }

    public function attributes(): array
    {
        return [
            'identificacao' => 'identificação',
            'nome'          => 'nome',
            'descricao'     => 'descrição',
            'cor'           => 'cor',
            'ativo'         => 'situação',
            'bairros'       => 'bairros',
            'trajeto'       => 'trajeto',
        ];
    }

    public function messages(): array
    {
        return [
            'identificacao.unique'     => 'Já existe uma rota com esta identificação.',
            'identificacao.alpha_dash' => 'Use apenas letras, números, hífen e sublinhado na identificação.',
            'nome.unique'              => 'Já existe uma rota com este nome.',
            'cor.regex'                => 'Escolha uma cor válida.',
            'bairros.required'         => 'Selecione ao menos um bairro de abrangência.',
            'bairros.min'              => 'Selecione ao menos um bairro de abrangência.',
            'bairros.*.exists'         => 'Um dos bairros selecionados está inativo ou não existe.',
            'trajeto.required'         => 'Desenhe ao menos um trecho no mapa.',
        ];
    }

    public function dadosDaRota(): array
    {
        return $this->safe()->only(['identificacao', 'nome', 'descricao', 'cor', 'ativo']);
    }

    public function bairros(): array
    {
        return array_map('intval', $this->validated('bairros'));
    }

    public function trajeto(): Trajeto
    {
        return Trajeto::deGeoJson($this->validated('trajeto'));
    }

    /**
     * Valida se o bairro está ativo ou já está vinculado a esta rota.
     */
    private function regraBairro(?Rota $rota): Exists
    {
        $vinculados = $rota?->bairros()->pluck('bairro.id')->all() ?? [];

        return Rule::exists('bairro', 'id')->where(function ($query) use ($vinculados) {
            $query->where('ativo', 1);

            if ($vinculados !== []) {
                $query->orWhereIn('id', $vinculados);
            }
        });
    }
}