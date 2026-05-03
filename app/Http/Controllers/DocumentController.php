<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $documents = $this->workspaceCollection('documents')
            ->map(function ($document, $id) {
                $document['id'] = $id;
                $document['type'] = $document['type'] ?? 'file';
                $document['category'] = $document['category'] ?? 'Uncategorized';
                $document['scope'] = $document['scope'] ?? 'general';
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
        $database = Firebase::database();
        $documentRules = Firebase::database()->getReference($this->currentWorkspaceSettingsPath('document_rules'))->getValue() ?? [
            'max_upload_size' => 25,
            'allowed_file_types' => ['PDF', 'DOCX'],
        ];

        $caseOptions = $this->workspaceCollection('cases')
            ->map(fn ($case, $id) => (object) [
                'id' => $id,
                'case_name' => $case['case_name'] ?? 'Untitled Case',
            ])
            ->values();

        $clientOptions = $this->workspaceCollection('clients')
            ->map(fn ($client, $id) => (object) [
                'id' => $id,
                'name' => $client['name'] ?? 'Unnamed Client',
            ])
            ->values();

        return view('modules.documents-upload', compact('documentRules', 'caseOptions', 'clientOptions'));
    }

    public function store(Request $request)
    {
        $database = Firebase::database();
        $documentRules = $database->getReference($this->currentWorkspaceSettingsPath('document_rules'))->getValue() ?? [
            'max_upload_size' => 25,
            'allowed_file_types' => ['PDF', 'DOCX'],
        ];
        $maxUploadKilobytes = ((int) ($documentRules['max_upload_size'] ?? 25)) * 1024;

        $request->validate([
            'documents.*' => ['nullable', 'file', 'max:' . $maxUploadKilobytes],
            'document_category' => ['required', 'string', 'in:Court Filing,Client Correspondence,Evidence,Contract'],
            'document_scope' => ['required', 'string', 'in:general,case,client'],
            'case_id' => ['nullable', 'string'],
            'client_id' => ['nullable', 'string'],
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

        $scope = $request->input('document_scope', 'general');
        $linkedEntity = $this->resolveLinkedEntity($database, $scope, $request->input('case_id'), $request->input('client_id'));

        if ($scope !== 'general' && !$linkedEntity) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['document_scope' => 'Please select a valid case or client for this document.']);
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
            $documentName = $this->buildDocumentName(
                $baseName,
                $scope,
                $linkedEntity['name'] ?? null
            );

            $database->getReference('documents')->push($this->workspacePayload([
                'type' => 'file',
                'category' => $request->input('document_category'),
                'scope' => $scope,
                'case_id' => $linkedEntity['type'] === 'case' ? $linkedEntity['id'] : null,
                'case_name' => $linkedEntity['type'] === 'case' ? $linkedEntity['name'] : null,
                'client_id' => $linkedEntity['type'] === 'client' ? $linkedEntity['id'] : null,
                'client_name' => $linkedEntity['type'] === 'client' ? $linkedEntity['name'] : null,
                'name' => $documentName,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'created_at' => now()->toIso8601String(),
            ]));
        }

        if ($linkUrl !== '') {
            $host = parse_url($linkUrl, PHP_URL_HOST) ?: 'External Link';
            $linkBaseName = $linkName !== '' ? $linkName : $host;

            $database->getReference('documents')->push($this->workspacePayload([
                'type' => 'link',
                'category' => $request->input('document_category'),
                'scope' => $scope,
                'case_id' => $linkedEntity['type'] === 'case' ? $linkedEntity['id'] : null,
                'case_name' => $linkedEntity['type'] === 'case' ? $linkedEntity['name'] : null,
                'client_id' => $linkedEntity['type'] === 'client' ? $linkedEntity['id'] : null,
                'client_name' => $linkedEntity['type'] === 'client' ? $linkedEntity['name'] : null,
                'name' => $this->buildDocumentName(
                    $linkBaseName,
                    $scope,
                    $linkedEntity['name'] ?? null
                ),
                'original_name' => $host,
                'link_url' => $linkUrl,
                'created_at' => now()->toIso8601String(),
            ]));
        }

        return redirect()->route('documents.index')->with('success', 'Document records saved successfully.');
    }

    public function show($id)
    {
        $document = $this->findWorkspaceRecord('documents', $id);

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
        $database = Firebase::database();
        $docData = $this->findWorkspaceRecord('documents', $id);
        if (!$docData) { abort(404); }

        $document = (object) $docData;
        $document->id = $id;
        $document->category = $document->category ?? 'Uncategorized';
        $document->scope = $document->scope ?? 'general';

        $caseOptions = $this->workspaceCollection('cases')
            ->map(fn ($case, $caseId) => (object) [
                'id' => $caseId,
                'case_name' => $case['case_name'] ?? 'Untitled Case',
            ])
            ->values();

        $clientOptions = $this->workspaceCollection('clients')
            ->map(fn ($client, $clientId) => (object) [
                'id' => $clientId,
                'name' => $client['name'] ?? 'Unnamed Client',
            ])
            ->values();

        return view('modules.documents-edit', compact('document', 'caseOptions', 'clientOptions'));
    }

    public function update(Request $request, $id)
    {
        $document = $this->findWorkspaceRecord('documents', $id);

        if (!$document) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:Court Filing,Client Correspondence,Evidence,Contract'],
            'document_scope' => ['required', 'string', 'in:general,case,client'],
            'case_id' => ['nullable', 'string'],
            'client_id' => ['nullable', 'string'],
            'link_url' => [($document['type'] ?? 'file') === 'link' ? 'required' : 'nullable', 'url'],
        ]);

        $linkedEntity = $this->resolveLinkedEntity(
            Firebase::database(),
            $validated['document_scope'],
            $validated['case_id'] ?? null,
            $validated['client_id'] ?? null
        );

        if ($validated['document_scope'] !== 'general' && !$linkedEntity) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['document_scope' => 'Please select a valid case or client for this document.']);
        }

        $payload = [
            'name' => $validated['name'],
            'category' => $validated['category'],
            'scope' => $validated['document_scope'],
            'case_id' => $linkedEntity['type'] === 'case' ? $linkedEntity['id'] : null,
            'case_name' => $linkedEntity['type'] === 'case' ? $linkedEntity['name'] : null,
            'client_id' => $linkedEntity['type'] === 'client' ? $linkedEntity['id'] : null,
            'client_name' => $linkedEntity['type'] === 'client' ? $linkedEntity['name'] : null,
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
        $doc = $this->findWorkspaceRecord('documents', $id);

        if (!$doc) {
            return redirect()->back()->with('error', 'Document not found.');
        }

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

    private function buildDocumentName(string $baseName, string $scope = 'general', ?string $linkedName = null): string
    {
        $cleanBaseName = trim($baseName) !== '' ? trim($baseName) : 'Document';
        $linkedLabel = $linkedName ? Str::upper(Str::slug($linkedName, '_')) : null;

        if ($scope === 'case' && $linkedLabel) {
            return 'CASE_' . $linkedLabel . '_' . $cleanBaseName;
        }

        if ($scope === 'client' && $linkedLabel) {
            return 'CLIENT_' . $linkedLabel . '_' . $cleanBaseName;
        }

        return $cleanBaseName;
    }

    private function resolveLinkedEntity($database, string $scope, ?string $caseId, ?string $clientId): ?array
    {
        return match ($scope) {
            'case' => $this->findLinkedCase($database, $caseId),
            'client' => $this->findLinkedClient($database, $clientId),
            default => ['type' => 'general', 'id' => null, 'name' => null],
        };
    }

    private function findLinkedCase($database, ?string $caseId): ?array
    {
        if (!$caseId) {
            return null;
        }

        $case = $this->findWorkspaceRecord('cases', $caseId);

        if (!$case) {
            return null;
        }

        return [
            'type' => 'case',
            'id' => $caseId,
            'name' => $case['case_name'] ?? 'Untitled Case',
        ];
    }

    private function findLinkedClient($database, ?string $clientId): ?array
    {
        if (!$clientId) {
            return null;
        }

        $client = $this->findWorkspaceRecord('clients', $clientId);

        if (!$client) {
            return null;
        }

        return [
            'type' => 'client',
            'id' => $clientId,
            'name' => $client['name'] ?? 'Unnamed Client',
        ];
    }
}
