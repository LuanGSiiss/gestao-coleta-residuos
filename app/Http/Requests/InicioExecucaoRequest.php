<?php

namespace App\Http\Requests;

use App\Models\Execucao;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class InicioExecucaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hora' => ['required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('hora')) {
                    return;
                }

                if ($this->inicio()->gt(now())) {
                    $validator->errors()->add('hora', 'A hora de início não pode ser posterior à hora atual.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['hora' => 'hora de início'];
    }

    /** Retorna o dia da execução com a hora informada e segundos zerados. */
    public function inicio(): CarbonImmutable
    {
        return $this->execucao()->data->setTimeFromTimeString($this->input('hora'));
    }

    private function execucao(): Execucao
    {
        return $this->route('execucao');
    }
}