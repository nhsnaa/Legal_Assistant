<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = collect(Firebase::database()->getReference('documents')->getValue() ?? [])->reverse();
        return view('modules.documents', compact('documents'));
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
public function edit($id)
{
    $docData = Firebase::database()->getReference('documents/' . $id)->getValue();
    if (!$docData) { abort(404); }

    $document = (object) $docData;
    $document->id = $id; // Ensure the ID is passed for the update route
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