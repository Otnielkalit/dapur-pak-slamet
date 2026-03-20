<?php

namespace App\Exports;

use App\Models\MealEntry;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Bom;
use League\Csv\Writer as CsvWriter;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export sinkron (langsung ke browser / folder Download) — tanpa queue.
 */
final class MealEntryTableExport
{
    private const CSV_DATE_FORMAT = 'd/m/Y H:i';

    /** Non-breaking space (UTF-8) — dipakai memanjangkan header agar Excel kurang “mepet”. */
    private const NBSP = "\xC2\xA0";

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
     * Header dengan padding NBSP supaya Excel biasanya membuka kolom sedikit lebih lebar.
     * (File CSV tidak bisa menyimpan lebar kolom; ini hanya “hint” visual.)
     *
     * @return list<string>
     */
    private static function csvHeaderRowWithWidthHint(): array
    {
        $labels = self::headers();
        /** @var list<int> target lebar kira-kira (jumlah karakter termasuk label) */
        $targets = [16, 36, 22, 34, 24, 18, 18, 24];

        return array_map(static function (string $label, int $target): string {
            $need = max(0, $target - mb_strlen($label));

            return $label.str_repeat(self::NBSP, $need);
        }, $labels, $targets);
    }

    /**
     * Excel sering mengonversi nomor HP panjang ke scientific notation.
     * Nilai seperti formula `="08123..."` dipaksa sebagai teks.
     */
    private static function csvExcelTextFormula(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return '="'.str_replace('"', '""', $value).'"';
    }

    private static function csvPhoneForExcel(?string $phone): string
    {
        $phone = trim((string) ($phone ?? ''));

        return self::csvExcelTextFormula($phone);
    }

    private static function csvCodeForExcel(?string $code): string
    {
        $code = trim((string) ($code ?? ''));
        if ($code === '') {
            return '';
        }

        // Kode murni angka / ada leading zero: hindari scientific & hilangnya 0 di depan.
        if (preg_match('/^\d+$/', $code) === 1 || preg_match('/^0\d+$/', $code) === 1) {
            return self::csvExcelTextFormula($code);
        }

        return $code;
    }

    /**
     * @return list<string>
     */
    private static function rowValuesCsv(MealEntry $entry): array
    {
        return [
            self::csvCodeForExcel($entry->customer_code),
            (string) ($entry->customer_name ?? ''),
            self::csvPhoneForExcel($entry->customer_phone),
            (string) ($entry->workplace_name ?? ''),
            $entry->eaten_at?->format(self::CSV_DATE_FORMAT) ?? '',
            'Rp '.number_format((int) ($entry->price ?? 0), 0, ',', '.'),
            $entry->paid ? 'Lunas' : 'Belum lunas',
            $entry->paid_at?->format(self::CSV_DATE_FORMAT) ?? '',
        ];
    }

    public static function downloadFilename(string $extension): string
    {
        return 'entry-makanan-'.now()->format('Y-m-d_His').'.'.$extension;
    }

    public static function downloadCsv(Builder $query): StreamedResponse
    {
        $csv = CsvWriter::createFromFileObject(new SplTempFileObject);
        $csv->setDelimiter(';');
        $csv->setEscape('\\');
        $csv->setEnclosure('"');

        // Membantu Excel (khusus regional Indonesia) membaca delimiter ';' dengan konsisten.
        $csv->insertOne(['sep=;']);
        $csv->insertOne(self::csvHeaderRowWithWidthHint());

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
