<?php

namespace Hamoi1\EloquentEncryptAble\Rules;

use Hamoi1\EloquentEncryptAble\Services\EloquentEncryptAbleService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class EncryptAbleUniqueRule implements ValidationRule
{
    protected $table;
    protected $column;
    protected $except;
    public function __construct($table, $column, $except = [])
    {
        $this->table = $table;
        $this->column = $column;
        $this->except = ['column' => data_get($except, 'column'), 'value' => data_get($except, 'value')];
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $encryptedValue = app(EloquentEncryptAbleService::class)->encrypt($value);
        $query = DB::table($this->table)->where($this->column, $encryptedValue);

        if (data_get($this->except, 'column') && data_get($this->except, 'value')) {
            $query->where($this->except['column'], '!=', $this->except['value']);
        }

        if ($query->exists()) {
            $fail("The $attribute has already been taken.");
        }
    }
}
