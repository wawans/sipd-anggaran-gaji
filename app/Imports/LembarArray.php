<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class LembarArray implements ToArray
{
    public array $baris = [];

    public function array(array $array): void
    {
        $this->baris = $array;
    }
}
