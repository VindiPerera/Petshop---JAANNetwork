<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns report data into a downloadable PDF or Excel file.
 *
 * $sections = [
 *   ['heading' => 'By branch', 'columns' => ['Branch', 'Revenue'], 'rows' => [['A', 10.5], ...],
 *    'align' => ['left', 'right'], 'totals' => ['Total', 10.5]  (optional)],
 * ]
 */
class Exporter
{
    public static function download(string $format, string $title, string $subtitle, array $sections, string $filename): Response
    {
        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', $filename);

        if ($format === 'xlsx') {
            return self::xlsx($title, $subtitle, $sections, $filename);
        }

        $wide = collect($sections)->contains(fn ($s) => count($s['columns']) > 6);
        return Pdf::loadView('exports.pdf', compact('title', 'subtitle', 'sections'))
            ->setPaper('a4', $wide ? 'landscape' : 'portrait')
            ->download($filename.'.pdf');
    }

    private static function xlsx(string $title, string $subtitle, array $sections, string $filename): Response
    {
        $path = tempnam(sys_get_temp_dir(), 'xl');
        $writer = new Writer();
        $writer->openToFile($path);

        $bold = (new Style())->withFontBold(true);
        $head = (new Style())->withFontBold(true)->withBackgroundColor('E0E7FF');

        $writer->addRow(self::styled([$title], (new Style())->withFontBold(true)->withFontSize(14)));
        $writer->addRow(Row::fromValues([$subtitle]));
        $writer->addRow(Row::fromValues([]));

        foreach ($sections as $s) {
            if (! empty($s['heading'])) {
                $writer->addRow(self::styled([$s['heading']], $bold));
            }
            $writer->addRow(self::styled($s['columns'], $head));
            foreach ($s['rows'] as $r) {
                $writer->addRow(Row::fromValues(array_map(fn ($v) => is_numeric($v) && ! is_string($v) ? $v + 0 : $v, $r)));
            }
            if (! empty($s['totals'])) {
                $writer->addRow(self::styled($s['totals'], $bold));
            }
            $writer->addRow(Row::fromValues([]));
        }
        $writer->close();

        return response()->download($path, $filename.'.xlsx')->deleteFileAfterSend(true);
    }

    private static function styled(array $values, Style $style): Row
    {
        return Row::fromValuesWithStyles($values, array_fill(0, count($values), $style));
    }
}
