<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = collect(Firebase::database()->getReference('documents')->getValue() ?? [])
            ->map(function ($document, $id) {
                $document['id'] = $id;
                $document['created_at'] = !empty($document['created_at'])
                    ? Carbon::parse($document['created_at'])
                    : null;
                return (object) $document;
            })
            ->reverse()
            ->values();

        return view('modules.documents', compact('documents'));
    }

    public function create()
    {
        return view('modules.documents-upload');
    }

    public function store(Request $request)
    {
        $database = Firebase::database();
        $files = $request->file('documents', []);

        foreach ($files as $file) {
            $path = $file->store('documents');

            $database->getReference('documents')->push([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'created_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()->route('documents.index')->with('success', 'Documents uploaded.');
    }

    public function show($id)
    {
        $document = Firebase::database()->getReference('documents/' . $id)->getValue();

        if (!$document || empty($document['file_path']) || !Storage::exists($document['file_path'])) {
            abort(404, 'File not found.');
        }

        return Storage::download($document['file_path'], $document['original_name'] ?? basename($document['file_path']));
    }

    public function edit($id)
    {
        $docData = Firebase::database()->getReference('documents/' . $id)->getValue();
        if (!$docData) { abort(404); }

        $document = (object) $docData;
        $document->id = $id;
        return view('modules.documents-edit', compact('document'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string']);
        Firebase::database()->getReference('documents/' . $id)->update([
            'name' => $request->name
        ]);
        return redirect()->route('documents.index')->with('success', 'Document renamed.');
    }

    public function destroy($id)
    {
        $ref = Firebase::database()->getReference('documents/' . $id);
        $doc = $ref->getValue();

        if (isset($doc['file_path'])) {
            Storage::delete($doc['file_path']);
        }

        $ref->remove();
        return redirect()->back()->with('success', 'Document deleted.');
    }
}
