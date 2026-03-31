<?php

use App\Models\Empresa;
use App\Models\EmpresaModulo;
use App\Models\Modulo;
use App\Models\Parametro;
use App\Models\ParametroValor;

if (! function_exists('tenant')) {
    function tenant(): ?Empresa
    {
        // Temporário: no futuro vamos resolver por auth, domínio ou sessão.
        return Empresa::query()->first();
    }
}

if (! function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        return tenant()?->id;
    }
}

if (! function_exists('modulo_habilitado')) {
    function modulo_habilitado(string $codigo): bool
    {
        $empresaId = tenant_id();

        if (! $empresaId) {
            return false;
        }

        $modulo = Modulo::query()->where('codigo', $codigo)->first();

        if (! $modulo) {
            return false;
        }

        return EmpresaModulo::query()
            ->where('empresa_id', $empresaId)
            ->where('modulo_id', $modulo->id)
            ->where('habilitado', true)
            ->exists();
    }
}

if (! function_exists('param_text')) {
    function param_text(string $codigo, $default = null): ?string
    {
        $parametro = Parametro::query()->where('codigo', $codigo)->first();

        if (! $parametro) {
            return $default;
        }

        $empresaId = tenant_id();

        if (! $empresaId) {
            return $parametro->valor_padrao ?? $default;
        }

        $valor = ParametroValor::query()
            ->where('parametro_id', $parametro->id)
            ->where('empresa_id', $empresaId)
            ->first();

        return $valor?->valor ?? $parametro->valor_padrao ?? $default;
    }
}

if (! function_exists('param_bool')) {
    function param_bool(string $codigo, bool $default = false): bool
    {
        $valor = param_text($codigo, $default ? '1' : '0');

        if (is_bool($valor)) {
            return $valor;
        }

        return in_array(strtolower((string) $valor), ['1', 'true', 'on', 'yes', 'sim'], true);
    }
}

if (! function_exists('param_num')) {
    function param_num(string $codigo, $default = 0): float
    {
        $valor = param_text($codigo, (string) $default);

        return (float) str_replace(',', '.', (string) $valor);
    }
}
