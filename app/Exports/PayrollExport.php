<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollExport implements WithMultipleSheets
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
//        $sheets['Payroll'] = new Payrolls($this->request);
        foreach ($this->request as $key => $value) {
            // sheet title maksimal 31 caracter
            $sheets[] = new Payrolls($value);
        }
        return $sheets;
    }
}