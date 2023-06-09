<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class DataExport implements WithMultipleSheets
{
    use Exportable;
    protected $request;
    function __construct(array $request) {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        $sheets['Data'] = new Datas($this->request);
        return $sheets;
    }
}