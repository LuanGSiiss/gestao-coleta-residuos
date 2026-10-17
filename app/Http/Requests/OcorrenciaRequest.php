<?php

namespace App\Http\Requests;

use App\Models\Execucao;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class OcorrenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo'    => ['required', 'string', 'max:120'],
            'descricao' => ['required', 'string', 'max:2000'],
            'data'      => ['required', 'date_format:Y-m-d'],
            'hora'      => ['required', 'date_format:H:i'],
            'endereco'  => ['nullable', 'string', 'max:200'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['data', 'hora'])) {
                    return;
                }

                $momento = $this->momento();
                $dia = $this->execucao()->data;

                if ($momento->gt(now())) {
                    $validator->errors()->add('hora', 'A data e a hora não podem ser posteriores ao momento atual.');
                } elseif ($momento->lt($dia)) {
                    $validator->errors()->add('data', "A ocorrência não pode ser anterior ao dia da coleta, {$dia->format('d/m/Y')}.");
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo'    => 'título',
            'descricao' => 'descrição',
            'data'      => 'data',
            'hora'      => 'hora',
            'endereco'  => 'endereço',
        ];
    }

    /** Retorna os dados da ocorrência. */
    public function dadosDaOcorrencia(): array
    {
        return [
            'titulo'    => $this->validated('titulo'),
            'descricao' => $this->validated('descricao'),
            'data_hora' => $this->momento(),
            'endereco'  => $this->validated('endereco'),
        ];
    }

    private function momento(): CarbonImmutable
    {
        return CarbonImmutable::parse("{$this->input('data')} {$this->input('hora')}");
    }

    private function execucao(): Execucao
    {
        return $this->route('execucao');
    }
}