<?php

namespace App\Exports;

use App\Models\LaporanPerbaikan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPerbaikanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->with('user')->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Lab',
            'No PC',
            'Kode PC',
            'Komponen',
            'Kondisi',
            'Keterangan Kerusakan',
            'Prioritas',
            'Status',
            'Pelapor',
        ];
    }

    public function map($laporan): array
    {
        // For Komponen and Kondisi, sometimes they are inside komponen_rusak json, or just strings.
        $komponen = collect($laporan->komponen_rusak);
        
        $namaKomponen = $laporan->komponen ?: $komponen->map(function($item) {
            return is_array($item) ? $item['komponen'] : $item;
        })->join(', ');

        $kondisi = $laporan->kondisi ?: $komponen->map(function($item) {
            return is_array($item) && isset($item['kondisi']) ? $item['kondisi'] : 'Rusak';
        })->join(', ');

        return [
            $laporan->tanggal_pengajuan ? $laporan->tanggal_pengajuan->format('Y-m-d') : '',
            $laporan->ruang_lab,
            $laporan->no_pc,
            $laporan->kode_pc ?: '-',
            $namaKomponen,
            $kondisi,
            $laporan->keterangan ?: '-',
            $laporan->prioritas,
            $laporan->status,
            $laporan->user ? $laporan->user->name : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Add borders and styling
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $range = 'A1:' . $highestColumn . $highestRow;

        $sheet->setAutoFilter('A1:' . $highestColumn . '1');

        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FF4F81BD'], // Soft blue
            ],
        ]);

        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        
        $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . $highestRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . $highestRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
