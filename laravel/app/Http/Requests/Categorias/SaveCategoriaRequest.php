<?php

declare(strict_types=1);

namespace App\Http\Requests\Categorias;

use App\Models\Categoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $categoria = $this->route('categoria');

        return $categoria instanceof Categoria
            ? $this->user()->can('update', $categoria)
            : $this->user()->can('create', Categoria::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nombre'))) {
            $this->merge(['nombre' => trim($this->input('nombre'))]);
        }
    }

    public function rules(): array
    {
        $unique = Rule::unique('categorias', 'nombre');
        if ($this->route('categoria') instanceof Categoria) {
            $unique->ignore($this->route('categoria')->getKey(), 'id_categoria');
        }

        return ['nombre' => ['required', 'string', 'max:100', $unique], 'publicado' => ['sometimes', 'boolean']];
    }
}
