<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class UniqueNomorSurat implements Rule
{
    protected $table;
    protected $ignoreId;

    public function __construct($table, $ignoreId = null)
    {
        $this->table = $table;
        $this->ignoreId = $ignoreId;
    }

    public function passes($attribute, $value)
    {
        $query = \DB::table($this->table)
            ->where('nomor_surat', $value)
            ->whereNull('deleted_at');

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        return $query->count() === 0;
    }

    public function message()
    {
        return 'The nomor surat has already been taken.';
    }
}
