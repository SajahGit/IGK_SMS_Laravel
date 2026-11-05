<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams CSV output a row at a time instead of buffering the whole
 * dataset in memory. Pass a lazy/cursor-based $rows iterable (e.g.
 * an Eloquent query's ->cursor()) so a report of thousands of rows
 * never materializes a full collection before writing output.
 */
class CsvStreamer
{
    /**
     * @param  string  $filename  Download filename, e.g. "fee-collection-2026-05-01-to-2026-05-31.csv"
     * @param  array<int,string>  $headers  CSV header row
     * @param  iterable<mixed>  $rows  Any iterable (array, Collection, LazyCollection, cursor)
     * @param  callable(mixed): array<int,mixed>  $mapRow  Maps one row item to a CSV row array
     */
    public static function download(string $filename, array $headers, iterable $rows, callable $mapRow): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $mapRow) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $mapRow($row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
