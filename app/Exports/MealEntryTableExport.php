<?php

namespace App\Exports;

use App\Models\MealEntry;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Bom;
use League\Csv\Writer as CsvWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export sinkron (langsung ke browser / folder Download) — tanpa queue.
 */
final class MealEntryTableExport
{
    private const XLSX_DATETIME_FORMAT = 'yyyy-mm-dd hh:mm:ss';

    private static function xlsxColumnStyles(): array
    {
        $fontSize = 11;

        $baseTextStyle = (new Style)
            ->setFontSize($fontSize)
            ->setCellAlignment(CellAlignment::LEFT)
            ->setShouldWrapText(true);

        $datetimeStyle = (clone $baseTextStyle)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText(false)
            ->setFormat(self::XLSX_DATETIME_FORMAT);

        $priceStyle = (clone $baseTextStyle)
            ->setCellAlignment(CellAlignment::RIGHT)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText(false)
            ->setFormat('"Rp"* #,##0');

        $statusStyle = (clone $baseTextStyle)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText(false);

        return [
            // 0: Code Unik
            0 => $baseTextStyle,
            // 1: Nama
            1 => $baseTextStyle,
            // 2: Nomor HP
            2 => $baseTextStyle,
            // 3: Tempat Kerja
            3 => $baseTextStyle,
            // 4: Tanggal Makan
            4 => $datetimeStyle,
            // 5: Harga
            5 => $priceStyle,
            // 6: Status
            6 => $statusStyle,
            // 7: Tanggal Lunas
            7 => $datetimeStyle,
        ];
    }

    private static function xlsxHeaderStyle(): Style
    {
        $border = new Border(
            new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
        );

        return (new Style)
            ->setFontBold()
            ->setFontSize(12)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::toARGB('4F81BD'))
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText(true)
            ->setBorder($border);
    }

    /**
     * @return list<string>
     */
    private static function headers(): array
    {
        return [
            'Code Unik',
            'Nama',
            'Nomor HP',
            'Tempat Kerja',
            'Tanggal Makan',
            'Harga',
            'Status',
            'Tanggal Lunas',
        ];
    }

    /**
     * @return list<int|string>
     */
    private static function rowValuesCsv(MealEntry $entry): array
    {
        return [
            $entry->customer_code,
            $entry->customer_name,
            $entry->customer_phone ?? '',
            $entry->workplace_name,
            $entry->eaten_at?->format('Y-m-d H:i:s') ?? '',
            $entry->price,
            $entry->paid ? 'lunas' : 'belum lunas',
            $entry->paid_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * XLSX perlu type yang benar (DateTimeInterface & int) supaya Excel ngasih formatting otomatis.
     *
     * @return array<int, null|bool|\DateInterval|\DateTimeInterface|float|int|string>
     */
    private static function rowValuesXlsx(MealEntry $entry): array
    {
        return [
            $entry->customer_code ?? '',
            $entry->customer_name ?? '',
            $entry->customer_phone ?? '',
            $entry->workplace_name ?? '',
            $entry->eaten_at,
            (int) ($entry->price ?? 0),
            $entry->paid ? 'lunas' : 'belum lunas',
            $entry->paid_at,
        ];
    }

    public static function downloadFilename(string $extension): string
    {
        return 'entry-makanan-'.now()->format('Y-m-d_His').'.'.$extension;
    }

    public static function downloadXlsx(Builder $query): StreamedResponse
    {
        $filename = self::downloadFilename('xlsx');

        return response()->streamDownload(function () use ($query, $filename): void {
            $writer = new XlsxWriter;

            // Lebar kolom agar tidak mepet.
            $options = $writer->getOptions();
            $options->setColumnWidth(14, 1); // Code Unik
            $options->setColumnWidth(28, 2); // Nama
            $options->setColumnWidth(18, 3); // Nomor HP
            $options->setColumnWidth(24, 4); // Tempat Kerja
            $options->setColumnWidth(22, 5); // Tanggal Makan
            $options->setColumnWidth(12, 6); // Harga
            $options->setColumnWidth(14, 7); // Status
            $options->setColumnWidth(22, 8); // Tanggal Lunas

            $writer->openToBrowser($filename);

            $headerValues = self::headers();
            $headerStyle = self::xlsxHeaderStyle();
            $headerColumnStyles = [];
            foreach (array_keys($headerValues) as $idx) {
                $headerColumnStyles[$idx] = $headerStyle;
            }

            $writer->addRow(Row::fromValuesWithStyles($headerValues, null, $headerColumnStyles));

            $bodyColumnStyles = self::xlsxColumnStyles();

            foreach ($query->clone()->cursor() as $mealEntry) {
                /** @var MealEntry $mealEntry */
                $writer->addRow(Row::fromValuesWithStyles(
                    self::rowValuesXlsx($mealEntry),
                    null,
                    $bodyColumnStyles
                ));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function downloadCsv(Builder $query): StreamedResponse
    {
        $csv = CsvWriter::createFromFileObject(new SplTempFileObject);
        $csv->insertOne(self::headers());

        foreach ($query->clone()->cursor() as $mealEntry) {
            /** @var MealEntry $mealEntry */
            $csv->insertOne(self::rowValuesCsv($mealEntry));
        }

        $filename = self::downloadFilename('csv');

        return response()->streamDownload(function () use ($csv): void {
            $csv->setOutputBOM(Bom::Utf8);

            echo $csv->toString();
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
