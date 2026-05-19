<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
                $document['updated_at'] = !empty($document['updated_at'])
                    ? Carbon::parse($document['updated_at'])
                    : null;
                $document['last_accessed_at'] = !empty($document['last_accessed_at'])
                    ? Carbon::parse($document['last_accessed_at'])
                    : null;
                $document['access_count'] = (int) ($document['access_count'] ?? 0);
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

        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        foreach ($files as $file) {
            $normalizedType = $this->normalizeFileType($file->getMimeType(), $file->getClientOriginalExtension());
            $allowedTypes = $documentRules['allowed_file_types'] ?? ['PDF', 'DOCX'];

            if (!in_array($normalizedType, $allowedTypes, true)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['documents' => 'The file type "' . strtolower($normalizedType) . '" is not allowed by the current document rules.']);
            }

            $path = $this->storeFileInSupabaseStorage($file);
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
                'storage_disk' => 'supabase',
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'created_by_id' => $actor['id'],
                'created_by_name' => $actor['name'],
                'updated_by_id' => $actor['id'],
                'updated_by_name' => $actor['name'],
            ]));
            $this->logActivity('document', 'Document', 'created', $documentName, [
                'type' => 'file',
            ]);
        }

        if ($linkUrl !== '') {
            $host = parse_url($linkUrl, PHP_URL_HOST) ?: 'External Link';
            $linkBaseName = $linkName !== ''
                ? $linkName
                : ($request->input('document_category') ?: 'Linked Document');

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
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'created_by_id' => $actor['id'],
                'created_by_name' => $actor['name'],
                'updated_by_id' => $actor['id'],
                'updated_by_name' => $actor['name'],
            ]));
            $this->logActivity('document', 'Document', 'created', $this->buildDocumentName(
                $linkBaseName,
                $scope,
                $linkedEntity['name'] ?? null
            ), [
                'type' => 'link',
            ]);
        }

        return redirect()->route('documents.index')->with('success', 'Document records saved successfully.');
    }

    public function show(Request $request, $id)
    {
        $document = $this->findWorkspaceRecord('documents', $id);

        if (!$document) {
            abort(404, 'Document not found.');
        }

        if (($document['type'] ?? 'file') === 'link' && !empty($document['link_url'])) {
            $this->recordDocumentAccess($id, $document, 'opened', $request);
            return redirect()->away($document['link_url']);
        }

        $storageDisk = $document['storage_disk'] ?? 'local';

        if ($storageDisk === 'supabase') {
            return $this->downloadFromSupabaseStorage($id, $document, $request);
        }

        if ($storageDisk === 'firebase') {
            return $this->downloadFromFirebaseStorage($id, $document, $request);
        }

        if (empty($document['file_path']) || !Storage::exists($document['file_path'])) {
            abort(404, 'File not found.');
        }

        $this->recordDocumentAccess($id, $document, 'downloaded', $request);

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
        $document->created_at = !empty($docData['created_at']) ? Carbon::parse($docData['created_at']) : null;
        $document->updated_at = !empty($docData['updated_at']) ? Carbon::parse($docData['updated_at']) : null;
        $document->last_accessed_at = !empty($docData['last_accessed_at']) ? Carbon::parse($docData['last_accessed_at']) : null;
        $document->access_count = (int) ($docData['access_count'] ?? 0);

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

        $actor = $this->currentActorMetadata();

        $payload = [
            'name' => $validated['name'],
            'category' => $validated['category'],
            'scope' => $validated['document_scope'],
            'case_id' => $linkedEntity['type'] === 'case' ? $linkedEntity['id'] : null,
            'case_name' => $linkedEntity['type'] === 'case' ? $linkedEntity['name'] : null,
            'client_id' => $linkedEntity['type'] === 'client' ? $linkedEntity['id'] : null,
            'client_name' => $linkedEntity['type'] === 'client' ? $linkedEntity['name'] : null,
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ];

        if (($document['type'] ?? 'file') === 'link') {
            $payload['link_url'] = $validated['link_url'];
            $payload['original_name'] = parse_url($validated['link_url'], PHP_URL_HOST) ?: ($document['original_name'] ?? 'External Link');
        }

        Firebase::database()->getReference('documents/' . $id)->update($payload);
        $this->logActivity('document', 'Document', 'updated', $validated['name'], [
            'type' => $document['type'] ?? 'file',
        ]);
        return redirect()->route('documents.index')->with('success', 'Document renamed.');
    }

    public function destroy(Request $request, $id)
    {
        $ref = Firebase::database()->getReference('documents/' . $id);
        $doc = $this->findWorkspaceRecord('documents', $id);

        if (!$doc) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Document not found.'], 404);
            }

            return redirect()->back()->with('error', 'Document not found.');
        }

        if (isset($doc['file_path'])) {
            $storageDisk = $doc['storage_disk'] ?? 'local';

            if ($storageDisk === 'supabase') {
                $this->deleteFromSupabaseStorage($doc['file_path']);
            } elseif ($storageDisk === 'firebase') {
                $this->deleteFromFirebaseStorage($doc['file_path']);
            } else {
                Storage::delete($doc['file_path']);
            }
        }

        $this->logActivity('document', 'Document', 'deleted', $doc['name'] ?? ($doc['original_name'] ?? 'Untitled Document'), [
            'type' => $doc['type'] ?? 'file',
        ]);
        $ref->remove();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document deleted.',
                'remaining' => $this->workspaceCollection('documents')->count(),
            ]);
        }

        return redirect()->back()->with('success', 'Document deleted.');
    }

    private function normalizeFileType(?string $mimeType, ?string $extension): string
    {
        if ($mimeType && isset(self::MIME_EXTENSION_MAP[$mimeType])) {
            return self::MIME_EXTENSION_MAP[$mimeType];
        }

        return strtoupper((string) $extension);
    }

    private function recordDocumentAccess(string $id, array $document, string $action, Request $request): void
    {
        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();
        $documentName = $document['name'] ?? ($document['original_name'] ?? 'Untitled Document');
        $accessCount = ((int) ($document['access_count'] ?? 0)) + 1;

        Firebase::database()->getReference('document_access_logs')->push($this->workspacePayload([
            'document_id' => $id,
            'document_name' => $documentName,
            'document_type' => $document['type'] ?? 'file',
            'storage_disk' => $document['storage_disk'] ?? null,
            'action' => $action,
            'actor_id' => $actor['id'],
            'actor_name' => $actor['name'],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'created_at' => $timestamp,
        ]));

        Firebase::database()->getReference('documents/' . $id)->update([
            'access_count' => $accessCount,
            'last_accessed_at' => $timestamp,
            'last_accessed_by_id' => $actor['id'],
            'last_accessed_by_name' => $actor['name'],
        ]);

        $this->logActivity('document', 'Document', $action, $documentName, [
            'document_id' => $id,
            'type' => $document['type'] ?? 'file',
        ]);
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

    private function storeFileInFirebaseStorage($file): string
    {
        $bucket = $this->firebaseStorageBucket();
        $workspaceId = $this->currentWorkspaceId() ?: 'general';
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = now()->format('YmdHis') . '_' . Str::random(12) . '_' . ($safeName !== '' ? $safeName : 'document');

        if ($extension !== '') {
            $fileName .= '.' . $extension;
        }

        $objectPath = 'documents/' . $workspaceId . '/' . $fileName;
        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            throw new \RuntimeException('Unable to read the uploaded file for Firebase Storage.');
        }

        try {
            $bucket->upload($stream, [
                'name' => $objectPath,
                'metadata' => [
                    'contentType' => $file->getMimeType(),
                ],
            ]);
        } finally {
            fclose($stream);
        }

        return $objectPath;
    }

    private function downloadFromFirebaseStorage(string $id, array $document, Request $request)
    {
        if (empty($document['file_path'])) {
            abort(404, 'File not found.');
        }

        $object = $this->firebaseStorageBucket()->object($document['file_path']);

        if (!$object->exists()) {
            abort(404, 'File not found.');
        }

        $contents = $object->downloadAsString();
        $fileName = $document['original_name'] ?? basename($document['file_path']);
        $mimeType = $document['mime_type'] ?? 'application/octet-stream';

        $this->recordDocumentAccess($id, $document, 'downloaded', $request);

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
        ]);
    }

    private function deleteFromFirebaseStorage(string $path): void
    {
        $object = $this->firebaseStorageBucket()->object($path);

        if ($object->exists()) {
            $object->delete();
        }
    }

    private function firebaseStorageBucket()
    {
        $storage = app('firebase.storage');
        $configuredBucket = config('firebase.projects.main.storage.default_bucket');

        return $configuredBucket
            ? $storage->getBucket($configuredBucket)
            : $storage->getBucket();
    }

    private function storeFileInSupabaseStorage($file): string
    {
        [$baseUrl, $serviceRoleKey, $bucket] = $this->supabaseStorageConfig();

        $workspaceId = $this->currentWorkspaceId() ?: 'general';
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = now()->format('YmdHis') . '_' . Str::random(12) . '_' . ($safeName !== '' ? $safeName : 'document');

        if ($extension !== '') {
            $fileName .= '.' . $extension;
        }

        $objectPath = 'documents/' . $workspaceId . '/' . $fileName;
        $uploadUrl = rtrim($baseUrl, '/') . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $this->encodeSupabaseObjectPath($objectPath);

        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $serviceRoleKey,
                'apikey' => $serviceRoleKey,
                'x-upsert' => 'false',
                'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
            ])
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType() ?: 'application/octet-stream')
            ->timeout(60)
            ->post($uploadUrl);

        if ($response->failed()) {
            throw new \RuntimeException('Supabase upload failed: ' . $response->body());
        }

        return $objectPath;
    }

    private function downloadFromSupabaseStorage(string $id, array $document, Request $request)
    {
        if (empty($document['file_path'])) {
            abort(404, 'File not found.');
        }

        [$baseUrl, $serviceRoleKey, $bucket] = $this->supabaseStorageConfig();
        $downloadUrl = rtrim($baseUrl, '/') . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $this->encodeSupabaseObjectPath($document['file_path']);

        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $serviceRoleKey,
                'apikey' => $serviceRoleKey,
            ])
            ->timeout(60)
            ->get($downloadUrl);

        if ($response->status() === 404) {
            abort(404, 'File not found.');
        }

        if ($response->failed()) {
            abort(500, 'Unable to download this file right now.');
        }

        $fileName = $document['original_name'] ?? basename($document['file_path']);
        $mimeType = $document['mime_type'] ?? ($response->header('Content-Type') ?: 'application/octet-stream');

        $this->recordDocumentAccess($id, $document, 'downloaded', $request);

        return response($response->body(), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
        ]);
    }

    private function deleteFromSupabaseStorage(string $path): void
    {
        [$baseUrl, $serviceRoleKey, $bucket] = $this->supabaseStorageConfig();
        $deleteUrl = rtrim($baseUrl, '/') . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $this->encodeSupabaseObjectPath($path);

        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $serviceRoleKey,
                'apikey' => $serviceRoleKey,
            ])
            ->timeout(30)
            ->delete($deleteUrl);

        if ($response->status() !== 404 && $response->failed()) {
            throw new \RuntimeException('Supabase delete failed: ' . $response->body());
        }
    }

    private function supabaseStorageConfig(): array
    {
        $baseUrl = config('services.supabase.url');
        $serviceRoleKey = config('services.supabase.service_role_key');
        $bucket = config('services.supabase.storage_bucket', 'documents');

        if (!$baseUrl || !$serviceRoleKey || !$bucket) {
            throw new \RuntimeException('Supabase Storage is not configured yet.');
        }

        return [$baseUrl, $serviceRoleKey, $bucket];
    }

    private function encodeSupabaseObjectPath(string $path): string
    {
        return collect(explode('/', trim($path, '/')))
            ->filter(fn ($segment) => $segment !== '')
            ->map(fn ($segment) => rawurlencode($segment))
            ->implode('/');
    }
}
