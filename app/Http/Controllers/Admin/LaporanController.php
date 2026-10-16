<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use App\Services\LaporanExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    public function __construct(private readonly LaporanExportService $laporan) {}

    public function index(Request $request): InertiaResponse
    {
        $taId = $request->integer('tahun_ajaran_id') ?: null;

        $this->laporan->setTahunAjaran($taId);

        return Inertia::render('admin/Laporan/Index', [
            'counts' => $this->laporan->counts(),
            'tahunAjaranOptions' => TahunAjaran::orderByDesc('active')
                ->orderBy('name')
                ->get(['id', 'name', 'active'])
                ->map(fn (TahunAjaran $t): array => [
                    'value' => $t->id,
                    'label' => $t->name.($t->active ? ' (aktif)' : ''),
                ]),
            'selectedTahunAjaran' => $taId,
        ]);
    }

    public function exportXlsx(Request $request): BinaryFileResponse
    {
        $taId = $request->integer('tahun_ajaran_id') ?: null;
        $this->laporan->setTahunAjaran($taId);

        $path = tempnam(sys_get_temp_dir(), 'laporan-').'.xlsx';

        $writer = new Writer(new Options);
        $writer->openToFile($path);

        $datasets = $this->laporan->datasets();

        foreach ($datasets as $index => $dataset) {
            if ($index > 0) {
                $writer->addNewSheetAndMakeItCurrent();
            }

            $sheet = $writer->getCurrentSheet();
            $sheet->setName($this->namaSheet($dataset['title']));

            // Header style: bold, biru tua, teks putih
            $headerStyle = (new Style)
                ->withFontBold(true)
                ->withFontSize(11)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor(Color::toARGB(Color::rgb(31, 97, 141)));

            // Alternate row style: biru sangat muda
            $altStyle = (new Style)->withBackgroundColor(Color::toARGB(Color::rgb(240, 248, 255)));

            $headerRow = Row::fromValuesWithStyle($dataset['headers'], $headerStyle);
            $writer->addRow($headerRow);

            $lebar = $this->laporan->hitungLebarKolom($dataset['headers'], $dataset['rows']);

            foreach ($dataset['rows'] as $i => $row) {
                $style = $i % 2 === 1 ? $altStyle : new Style;
                $writer->addRow(Row::fromValuesWithStyle($row, $style));
            }

            // Set column widths
            foreach ($lebar as $col => $width) {
                $sheet->setColumnWidth($width, $col + 1);
            }
        }

        $writer->close();

        $namaFile = $taId
            ? 'laporan-TA-'.$taId.'-'.date('Y-m-d').'.xlsx'
            : 'laporan-seluruh-data-'.date('Y-m-d').'.xlsx';

        return response()->download($path, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request): BinaryFileResponse
    {
        $taId = $request->integer('tahun_ajaran_id') ?: null;
        $this->laporan->setTahunAjaran($taId);

        $datasets = $this->laporan->datasets();
        $totalRows = array_sum(array_map(fn (array $d) => count($d['rows']), $datasets));

        $taName = null;
        if ($taId) {
            $taName = TahunAjaran::find($taId)?->name;
        }

        $html = view('laporan.pdf', [
            'datasets' => $datasets,
            'totalRows' => $totalRows,
            'tahunAjaran' => $taName,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true);

        $path = tempnam(sys_get_temp_dir(), 'laporan-').'.pdf';
        $pdf->save($path);

        $namaFile = $taId
            ? 'laporan-TA-'.$taId.'-'.date('Y-m-d').'.pdf'
            : 'laporan-seluruh-data-'.date('Y-m-d').'.pdf';

        return response()->download($path, $namaFile, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    private function namaSheet(string $name): string
    {
        $name = preg_replace('/[:\\\\\/\?\*\[\]]/', '-', $name);

        return mb_substr($name, 0, 31);
    }
}
