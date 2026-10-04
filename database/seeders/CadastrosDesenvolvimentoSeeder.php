<?php

namespace Database\Seeders;

use App\Models\Marca;
use App\Models\Veiculo;
use App\Enums\TipoVeiculo;
use App\Models\Funcionario;
use App\Enums\TipoFuncionario;
use Illuminate\Database\Seeder;

/**
 * Cadastro de dados para desenvolvimento., sendo fictícios.
 */
class CadastrosDesenvolvimentoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Mercedes-Benz', 'Volkswagen', 'Volvo', 'Iveco'] as $nome) {
            Marca::firstOrCreate(['nome' => $nome], ['ativo' => true]);
        }

        $motoristas = [
            ['cpf' => '29141777638', 'nome' => 'Carlos Eduardo Souza', 'cnh' => '04512378960', 'categoria' => 'D'],
            ['cpf' => '01152449303', 'nome' => 'Marcos Antônio Lima',  'cnh' => '07823145690', 'categoria' => 'C'],
        ];

        foreach ($motoristas as $motorista) {
            Funcionario::firstOrCreate(['cpf' => $motorista['cpf']], [
                'nome'                  => $motorista['nome'],
                'cargo'                 => 'Motorista de caminhão',
                'tipo'                  => TipoFuncionario::MOTORISTA,
                'carga_horaria_semanal' => 44,
                'cnh'                   => $motorista['cnh'],
                'cnh_categoria'         => $motorista['categoria'],
                'cnh_validade'          => now()->addYears(2)->toDateString(),
                'ativo'                 => true,
            ]);
        }

        $coletores = [
            ['cpf' => '39825979194', 'nome' => 'João Pedro Alves'],
            ['cpf' => '34167211017', 'nome' => 'Rafael Moreira'],
            ['cpf' => '94580730224', 'nome' => 'Luiz Fernando Costa'],
        ];

        foreach ($coletores as $coletor) {
            Funcionario::firstOrCreate(['cpf' => $coletor['cpf']], [
                'nome'                  => $coletor['nome'],
                'cargo'                 => 'Coletor de resíduos',
                'tipo'                  => TipoFuncionario::COLETOR,
                'carga_horaria_semanal' => 44,
                'ativo'                 => true,
            ]);
        }

        $veiculos = [
            ['placa' => 'RSU1A23', 'modelo' => 'Atego 1719', 'marca' => 'Mercedes-Benz', 'tipo' => TipoVeiculo::COMPACTADOR, 'ano' => 2021, 'capacidade' => 10.00],
            ['placa' => 'MTL4521', 'modelo' => 'Delivery 9.170', 'marca' => 'Volkswagen', 'tipo' => TipoVeiculo::CARROCERIA_ABERTA, 'ano' => 2016, 'capacidade' => 5.50],
        ];

        foreach ($veiculos as $veiculo) {
            Veiculo::firstOrCreate(['placa' => $veiculo['placa']], [
                'modelo'               => $veiculo['modelo'],
                'marca_id'             => Marca::where('nome', $veiculo['marca'])->value('id'),
                'tipo'                 => $veiculo['tipo'],
                'ano'                  => $veiculo['ano'],
                'capacidade_toneladas' => $veiculo['capacidade'],
                'ativo'                => true,
            ]);
        }
    }
}