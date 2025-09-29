<?php namespace App\Http\Controllers;

use App\Models\DestDir;
use App\Models\Document;
use Illuminate\Http\Request;

class MappingController extends Controller
{
    public function index(Request $request)
    {
        $documents = $this->buildQuery($request->source_path, $request->status);

        $documents = $documents->paginate(1000);

        $totalCount = Document::count();

        $percentage = $totalCount ? round(100 - Document::whereNull('destination_path')->count() / $totalCount * 100, 2)
            : 0;

        return view('mapping.index', [
            'sourcePath' => $request->source_path ?? '',
            'destinationPath' => $request->destination_path ?? '',
            'status' => $request->status ?? 'unmapped',
            'percentageMapped' => $percentage,
            'total' => $documents->total(),
            'rows' => $documents,
            'destinationsDirs' => DestDir::orderBy('path')->get(),
        ]);
    }

    public function import()
    {
        return view('mapping.import');
    }

    public function processImport(Request $request)
    {
        $path = $request->file('csv_file')->getRealPath();

        // truncate the documents table
        \DB::table('documents')->truncate();


        foreach (file($path) as $index => $line) {
            $parts = str_getcsv($line, ';');

            // skip eheader line
            if ($index === 0) {
                continue;
            }

            if (count($parts) < 2) {
                die('Invalid CSV format on line: ' . ($index + 1));
            }

            Document::create([
                'source_path' => $parts[0],
                'source_relation_number' => $parts[1],
                'source_relation_name' => $parts[2] ?? null,
            ]);
        }

        return redirect()->route('mapping.index');
    }

    public function update(Request $request)
    {
        $documents = $this->buildQuery($request->source_path, $request->status);

        if ($request->dest_dir_id != "-") {
            $destDir = $request->dest_dir_id != '' ?
                DestDir::where('id', $request->dest_dir_id)->first()->path
                : null;
        } else {
            $destDir = "-";
        }

        $documents->update(['destination_path' => $destDir]);

        return redirect()->route('mapping.index', [
            'source_path' => '',
            'status' => 'unmapped',
        ]);
    }

    protected function buildQuery($sourcePath, $status)
    {
        $documents = Document::query();

        $sourcePath = str_replace("*", "%", $sourcePath ?? '');

        // only allow 3 wildcard characters
        if (substr_count($sourcePath, '%') > 3) {
            abort(400, 'Too many wildcard characters');
        }


        $sourcePath && $documents->where('source_path', 'like', '%' . $sourcePath . '%');

        switch ($status) {
            case 'mapped':
                $documents->whereNotNull('destination_path');
                break;
            case 'unmapped':
                $documents->whereNull('destination_path');
                break;
        }

        return $documents;
    }

    public function download()
    {
        $filename = 'mapping_export_' . date('Y-m-d_H-i-s') . '.csv';
        $documents = Document::all();
        $handle = fopen($filename, 'w+');

        fputcsv($handle, ['source_path', 'source_relation_number', 'source_relation_name', 'destination_path'], ';');
        foreach ($documents as $document) {
            fputcsv($handle, [$document->source_path, $document->source_relation_number, $document->source_relation_name, $document->destination_path], ';');
        }

        fclose($handle);

        return response()->download($filename)->deleteFileAfterSend(true);
    }
}
