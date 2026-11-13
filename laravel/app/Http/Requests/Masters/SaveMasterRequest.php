<?php

declare(strict_types=1);

namespace App\Http\Requests\Masters;

use App\Services\Masters\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(app(MasterData::class)->permission($this->route('maestro'), 'crear_editar'));
    }

    public function rules(MasterData $masters): array
    {
        $module = $this->route('maestro');
        $model = $masters->model($module);
        $rules = [];
        foreach ($masters->definition($module)['fields'] as $name => $field) {
            $rules[$name] = explode('|', $field['rules']);
            if ($field['unique'] ?? false) {
                $rules[$name][] = Rule::unique($model->getTable(), $name)->ignore($this->route('id'), $model->getKeyName());
            }
        }

        return $rules;
    }
}
