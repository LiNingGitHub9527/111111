<?php

namespace App\Support\Office\Excel\Export;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;
use  \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
// use Illuminate\Support\LazyCollection;

class SimpleExport extends StringValueBinder implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithCustomValueBinder
{
    use Exportable;

    protected $headData;

    protected $data;

    protected $colsNum;

    protected $rowsNum;

    protected $options;

    public function __construct($headData, $data, $options = [])
    {
        $this->headData = $headData;
        $this->data = $data;
        $this->colsNum = count($headData);
        $this->rowsNum = count($data) + 1;
        $this->options = $options;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->headData as $text) {
            $headings[] = $text;
        }

        return $headings;
    }

    public function collection()
    {
        $cols = [];
        foreach ($this->headData as $key => $val) {
            $cols[] = explode('/', $key);
        }

        $rows = [];
        foreach ($this->data as $d) {
            $row = [];
            foreach ($cols as $c) {
                if (count($c) == 1) {
                    $cell = $d[$c['0']];
                } else {
                    if (isset($d[$c['0']]) && isset($d[$c['0']][$c['1']])) {
                        $cell = $d[$c['0']][$c['1']];
                    } elseif (!isset($d[$c['0']])) {
                        $cell = 'なし';
                    }
                }
                // $row[] = nl2br($this->filter($cell));
                $row[] = $this->filter($cell);
            }
            $rows[] = $row;
        }
        return new Collection($rows);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                if (!empty($this->options)) {
                    if (isset($this->options['width'])) {
                        $width = $this->options['width'];
                        foreach ($width as $key => $val) {
                            if ($val > 0) {
                                $worksheet->getColumnDimension($key)->setAutoSize(false)->setWidth($val);
                            } else {
                                $worksheet->getColumnDimension($key)->setAutoSize(true);
                            }
                        }
                    }
                }

                $spreadsheet = $worksheet->getParent();
                $spreadsheet->getProperties()->setCreator('Kamome');
                $spreadsheet->getDefaultStyle()->getFont()->setName('宋体');
                $headCellStart = 'A1';
                $headCellEnd = $worksheet->getCellByColumnAndRow($this->colsNum, 1)->getCoordinate();
                // head
                $headCellRange = $headCellStart.':'.$headCellEnd;
                $worksheet->getStyle($headCellRange)->getFont()
                    ->setSize(12)->setBold(true);
                $worksheet->getRowDimension(1)->setRowHeight(20);
                $worksheet->getStyle($headCellRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFF99');
                // all
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '00000000'],
                        ],
                    ],
                ];
                $cellStart = 'A1';
                $cellEnd = $worksheet->getCellByColumnAndRow($this->colsNum, $this->rowsNum)->getCoordinate();
                $cellRange = $cellStart.':'.$cellEnd;
                $worksheet->getStyle($cellRange)->applyFromArray($styleArray);
            },
        ];
    }

    protected function filter($data)
    {
        $search = [
            '/&amp;/ism',
            '/&quot;/ism',
            '/&#039;/ism',
            '/&lt;/ism',
            '/&gt;/ism'
        ];
        $replace = [
            '&',
            '"',
            '\'',
            '<',
            '>'
        ];
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = preg_replace($search, $replace, $value);
            }
        } else {
            $data = preg_replace($search, $replace, $data);
        }

        return $data;
    }
}
