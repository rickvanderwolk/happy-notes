<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProfileExportController extends Controller
{
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse|StreamedResponse
    {
        $format = $request->query('format', 'json');
        $notes = Note::all();

        $exportData = [];
        foreach ($notes as $note) {
            $exportData[] = [
                'uuid' => $note->uuid,
                'title' => $note->title,
                'body' => json_decode($note->body, true),
                'emojis' => $note->emojis,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
            ];
        }

        if ($format === 'json') {
            $filename = 'happynotes_export_' . date('Y-m-d_His') . '.json';

            return response()->streamDownload(function () use ($exportData) {
                echo json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }, $filename, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }

        if ($format === 'csv') {
            $filename = 'happynotes_export_' . date('Y-m-d_His') . '.csv';

            return response()->streamDownload(function () use ($exportData) {
                echo $this->convertToCsv($exportData);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }

        return response()->json(['error' => 'Invalid format'], 400);
    }

    private function convertToCsv(array $data): string
    {
        $csv = fopen('php://temp', 'r+');
        if ($csv === false) {
            throw new RuntimeException('Unable to open temporary memory for CSV export.');
        }

        if (!empty($data)) {
            fputcsv($csv, array_keys($data[0]));

            foreach ($data as $row) {
                $formattedRow = array_map(function ($value) {
                    return is_array($value)
                        ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : $value;
                }, array_values($row));

                fputcsv($csv, $formattedRow);
            }
        }

        rewind($csv);
        $output = stream_get_contents($csv);
        fclose($csv);

        return $output;
    }
}
