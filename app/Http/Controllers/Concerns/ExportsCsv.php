<?php

namespace App\Http\Controllers\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV natif (fputcsv + StreamedResponse), sans dépendance externe,
 * conformément à architecturev1.md §K ("CSV natif suffit pour la V1, pas
 * besoin de maatwebsite/excel pour un simple export tabulaire"). Excel/PDF
 * restent prévus via des packages optionnels (voir ExportsToExcel/PDF si
 * ajoutés plus tard), gardés derrière class_exists() pour ne jamais casser
 * l'app si le package n'est pas installé.
 *
 * Usage dans un contrôleur admin :
 *   return $this->streamCsv(
 *       Application::query()->with('applicationCall')->cursor(),
 *       ['ID', 'Candidat', 'Email', 'Statut', 'Créée le'],
 *       fn ($application) => [
 *           $application->id,
 *           $application->nom_complet,
 *           $application->email,
 *           $application->statut->value,
 *           $application->created_at->toDateString(),
 *       ],
 *       'candidatures'
 *   );
 */
trait ExportsCsv
{
    protected function streamCsv(iterable $rows, array $headers, \Closure $mapRow, string $filenamePrefix): StreamedResponse
    {
        $filename = sprintf('%s-%s.csv', $filenamePrefix, now()->format('Y-m-d-His'));

        return response()->streamDownload(function () use ($rows, $headers, $mapRow) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 : garantit que les accents (é, è, à...) s'affichent
            // correctement à l'ouverture dans Excel, très utilisé côté
            // administration de l'association.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $mapRow($row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
