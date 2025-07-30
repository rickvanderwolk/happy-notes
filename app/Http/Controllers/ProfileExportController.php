<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProfileExportController extends Controller
{
    public function export(Request $request): \Illuminate\Http\JsonResponse|StreamedResponse
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
            $jsonOutput = json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            return Response::streamDownload(function () use ($jsonOutput) {
                echo $jsonOutput;
            }, 'user_data.json', [
                'Content-Type' => 'application/json',
            ]);
        }

        if ($format === 'csv') {
            $csvOutput = $this->convertToCsv($exportData);

            return Response::streamDownload(function () use ($csvOutput) {
                echo $csvOutput;
            }, 'user_data.csv', [
                'Content-Type' => 'text/csv',
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
