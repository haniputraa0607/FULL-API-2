<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use App\Lib\MyHelper;

class Payrolls implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $request;

    function __construct($request) {
        $this->request = $request;
    }

    public function array(): array
    {
    	return $this->request['body'];
    }

    public function headings(): array
    {
        
        return $this->request['head'];
    	// return array_map(function($x){return ucwords(str_replace('_', ' ', $x));}, array_keys($this->outlets[0]??[]));
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return  $this->request['start_date'].'-'.$this->request['end_date'];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $last = count($this->request['body']);
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ]
                    ],
                    'numberFormat' => [
                        'formatCode' => '#,##0'
                    ],
                ];
                $styleHead = [
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'rotation' => 90,
                        'startColor' => [
                            'argb' => 'FFA0A0A0',
                        ],
                        'endColor' => [
                            'argb' => 'FFFFFFFF',
                        ],
                    ],
                ];
                $x_coor = MyHelper::getNameFromNumber(count($this->request['head']??[]));
                $event->sheet->getStyle('A1:'.$x_coor.($last+1))->applyFromArray($styleArray);
                $event->sheet->getStyle('AA1:'.$x_coor.($last+1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);;
                $headRange = 'A1:'.$x_coor.'1';
                $event->sheet->getStyle($headRange)->applyFromArray($styleHead);
            },
        ];
    }
}