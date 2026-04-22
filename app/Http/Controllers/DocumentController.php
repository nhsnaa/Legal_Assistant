<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::latest()->get();

        return view('modules.documents', compact('documents'));
    }

    public function create()
    {
        return view('modules.documents-upload');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'documents' => ['required', 'array'],
            'documents.*' => ['file', 'max:10240'],
        ], [
            'documents.required' => 'Please select at least one document to upload.',
            'documents.*.max' => 'Each document must not be larger than 10MB.',
        ]);

        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
        $files = $request->file('documents', []);

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, $allowedExtensions, true)) {
                return back()
                    ->withErrors(['documents' => 'Only PDF, DOC, DOCX, and TXT files are allowed.'])
                    ->withInput();
            }
        }

        $created = 0;

        foreach ($files as $file) {
            $path = $file->store('documents');

            Document::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);

            $created++;
        }

        $redirectTo = $request->input('redirect_to');

        return $redirectTo
            ? redirect()->to($redirectTo)->with('success', $created . ' document(s) uploaded successfully.')
            : redirect()->route('documents.index')->with('success', $created . ' document(s) uploaded successfully.');
    }

    public function show(Document $document)
    {
        if (!Storage::exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::download($document->file_path, $document->original_name);
    }

    public function edit(Document $document)
    {
        return view('modules.documents-edit', compact('document'));
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $document->update([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document updated successfully.');
    }

    public function destroy(Document $document)
    {
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document deleted successfully.');
    }
}
