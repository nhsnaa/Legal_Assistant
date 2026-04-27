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
                $document['type'] = $document['type'] ?? 'file';
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
        $request->validate([
            'documents.*' => ['nullable', 'file', 'max:10240'],
            'link_url' => ['nullable', 'url'],
            'link_name' => ['nullable', 'string', 'max:255'],
        ]);

        $files = $request->file('documents', []);
        $linkUrl = trim((string) $request->input('link_url', ''));
        $linkName = trim((string) $request->input('link_name', ''));

        if (empty($files) && $linkUrl === '') {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['documents' => 'Please upload at least one file or provide a document link.']);
        }

        foreach ($files as $file) {
            $path = $file->store('documents');

            $database->getReference('documents')->push([
                'type' => 'file',
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'created_at' => now()->toIso8601String(),
            ]);
        }

        if ($linkUrl !== '') {
            $host = parse_url($linkUrl, PHP_URL_HOST) ?: 'External Link';

            $database->getReference('documents')->push([
                'type' => 'link',
                'name' => $linkName !== '' ? $linkName : $host,
                'original_name' => $host,
                'link_url' => $linkUrl,
                'created_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()->route('documents.index')->with('success', 'Document records saved successfully.');
    }

    public function show($id)
    {
        $document = Firebase::database()->getReference('documents/' . $id)->getValue();

        if (!$document) {
            abort(404, 'Document not found.');
        }

        if (($document['type'] ?? 'file') === 'link' && !empty($document['link_url'])) {
            return redirect()->away($document['link_url']);
        }

        if (empty($document['file_path']) || !Storage::exists($document['file_path'])) {
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
        $document = Firebase::database()->getReference('documents/' . $id)->getValue();

        if (!$document) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'link_url' => [($document['type'] ?? 'file') === 'link' ? 'required' : 'nullable', 'url'],
        ]);

        $payload = [
            'name' => $validated['name'],
        ];

        if (($document['type'] ?? 'file') === 'link') {
            $payload['link_url'] = $validated['link_url'];
            $payload['original_name'] = parse_url($validated['link_url'], PHP_URL_HOST) ?: ($document['original_name'] ?? 'External Link');
        }

        Firebase::database()->getReference('documents/' . $id)->update($payload);
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
