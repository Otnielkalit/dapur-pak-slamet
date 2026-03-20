<?php

namespace App\Exports;

use App\Models\MealEntry;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Bom;
use League\Csv\Writer as CsvWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export sinkron (langsung ke browser / folder Download) — tanpa queue.
 */
final class MealEntryTableExport
{
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
    private static function rowValues(MealEntry $entry): array
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

    public static function downloadFilename(string $extension): string
    {
        return 'entry-makanan-'.now()->format('Y-m-d_His').'.'.$extension;
    }

    public static function downloadXlsx(Builder $query): StreamedResponse
    {
        $filename = self::downloadFilename('xlsx');

        return response()->streamDownload(function () use ($query, $filename): void {
            $writer = new XlsxWriter;
            $writer->openToBrowser($filename);

            $writer->addRow(Row::fromValues(self::headers()));

            foreach ($query->clone()->cursor() as $mealEntry) {
                /** @var MealEntry $mealEntry */
                $writer->addRow(Row::fromValues(self::rowValues($mealEntry)));
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
            $csv->insertOne(self::rowValues($mealEntry));
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
