<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;

class DocumentController extends Controller
{
    private const MIME_EXTENSION_MAP = [
        'application/pdf' => 'PDF',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
        'image/jpeg' => 'JPG',
        'image/png' => 'PNG',
        'text/plain' => 'TXT',
        'application/msword' => 'DOCX',
    ];

    public function index()
    {
        $documents = collect(Firebase::database()->getReference('documents')->getValue() ?? [])
            ->map(function ($document, $id) {
                $document['id'] = $id;
                $document['type'] = $document['type'] ?? 'file';
                $document['category'] = $document['category'] ?? 'Uncategorized';
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
        $documentRules = Firebase::database()->getReference('settings/document_rules')->getValue() ?? [
            'max_upload_size' => 25,
            'allowed_file_types' => ['PDF', 'DOCX'],
            'file_naming_rule' => '',
        ];

        return view('modules.documents-upload', compact('documentRules'));
    }

    public function store(Request $request)
    {
        $database = Firebase::database();
        $documentRules = $database->getReference('settings/document_rules')->getValue() ?? [
            'max_upload_size' => 25,
            'allowed_file_types' => ['PDF', 'DOCX'],
            'file_naming_rule' => '',
        ];
        $maxUploadKilobytes = ((int) ($documentRules['max_upload_size'] ?? 25)) * 1024;

        $request->validate([
            'documents.*' => ['nullable', 'file', 'max:' . $maxUploadKilobytes],
            'document_category' => ['required', 'string', 'in:Court Filing,Client Correspondence,Evidence,Contract'],
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
            $normalizedType = $this->normalizeFileType($file->getMimeType(), $file->getClientOriginalExtension());
            $allowedTypes = $documentRules['allowed_file_types'] ?? ['PDF', 'DOCX'];

            if (!in_array($normalizedType, $allowedTypes, true)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['documents' => 'The file type "' . strtolower($normalizedType) . '" is not allowed by the current document rules.']);
            }

            $path = $file->store('documents');
            $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $documentName = $this->buildDocumentName($baseName, $documentRules['file_naming_rule'] ?? '');

            $database->getReference('documents')->push([
                'type' => 'file',
                'category' => $request->input('document_category'),
                'name' => $documentName,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'created_at' => now()->toIso8601String(),
            ]);
        }

        if ($linkUrl !== '') {
            $host = parse_url($linkUrl, PHP_URL_HOST) ?: 'External Link';
            $linkBaseName = $linkName !== '' ? $linkName : $host;

            $database->getReference('documents')->push([
                'type' => 'link',
                'category' => $request->input('document_category'),
                'name' => $this->buildDocumentName($linkBaseName, $documentRules['file_naming_rule'] ?? ''),
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
            'category' => ['required', 'string', 'in:Court Filing,Client Correspondence,Evidence,Contract'],
            'link_url' => [($document['type'] ?? 'file') === 'link' ? 'required' : 'nullable', 'url'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'category' => $validated['category'],
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

    private function normalizeFileType(?string $mimeType, ?string $extension): string
    {
        if ($mimeType && isset(self::MIME_EXTENSION_MAP[$mimeType])) {
            return self::MIME_EXTENSION_MAP[$mimeType];
        }

        return strtoupper((string) $extension);
    }

    private function buildDocumentName(string $baseName, string $namingRule): string
    {
        $cleanBaseName = trim($baseName) !== '' ? trim($baseName) : 'Document';

        return match ($namingRule) {
            'DATE_PREFIX' => now()->format('Ymd') . '_' . $cleanBaseName,
            'CLIENT_DOC' => 'CLIENT_' . $cleanBaseName,
            'CASE_DOC' => 'CASE_' . $cleanBaseName,
            default => $cleanBaseName,
        };
    }
}
