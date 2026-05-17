<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function girffonCustomDesignJsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function girffonCustomDesignRequestData(): array
{
    $post = is_array($_POST) ? $_POST : [];
    $rawPayload = file_get_contents('php://input');
    $data = $post;

    if (is_string($rawPayload) && trim($rawPayload) !== '') {
        $decoded = json_decode($rawPayload, true);
        if (is_array($decoded)) {
            $data = array_merge($data, $decoded);
        }
    }

    foreach ($data as $key => $value) {
        if (!is_string($value)) {
            continue;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            continue;
        }

        $firstChar = $trimmed[0];
        if ($firstChar !== '{' && $firstChar !== '[') {
            continue;
        }

        $decodedValue = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedValue)) {
            $data[$key] = $decodedValue;
        }
    }

    return $data;
}

function girffonCustomDesignNormalizePath(string $path): array
{
    $segments = array_values(array_filter(array_map('trim', explode('/', str_replace('\\', '/', $path))), static function ($segment): bool {
        return $segment !== '';
    }));

    foreach ($segments as $segment) {
        if ($segment === '.' || $segment === '..' || preg_match('/[[:cntrl:]]/', $segment)) {
            girffonCustomDesignJsonResponse(422, [
                'success' => false,
                'message' => 'Invalid project path.',
            ]);
        }
    }

    return $segments;
}

function girffonCustomDesignStorageOwner(): string
{
    $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['girffon_user_id'] ?? 0);
    if ($userId > 0) {
        return 'user-' . $userId;
    }

    $sessionId = session_id();
    if (!is_string($sessionId) || $sessionId === '') {
        $sessionId = 'guest';
    }

    return 'session-' . sha1($sessionId);
}

function girffonCustomDesignBaseDirectory(): string
{
    $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'custom-design-projects' . DIRECTORY_SEPARATOR . girffonCustomDesignStorageOwner();
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        girffonCustomDesignJsonResponse(500, [
            'success' => false,
            'message' => 'Unable to create custom design storage.',
        ]);
    }

    return $directory;
}

function girffonCustomDesignStructurePath(): string
{
    return girffonCustomDesignBaseDirectory() . DIRECTORY_SEPARATOR . 'folder-structure.json';
}

function girffonCustomDesignDefaultStructure(): array
{
    return [
        'folders' => [],
        'files' => [],
    ];
}

function girffonCustomDesignNormalizeStructureNode($node): array
{
    $folders = [];
    $files = [];

    if (is_array($node) && isset($node['folders']) && is_array($node['folders'])) {
        foreach ($node['folders'] as $name => $child) {
            $folderName = trim((string) $name);
            if ($folderName === '' || $folderName === '.' || $folderName === '..') {
                continue;
            }
            $folders[$folderName] = girffonCustomDesignNormalizeStructureNode($child);
        }
    }

    if (is_array($node) && isset($node['files']) && is_array($node['files'])) {
        foreach ($node['files'] as $fileName) {
            $normalizedFileName = trim((string) $fileName);
            if ($normalizedFileName === '' || $normalizedFileName === '.' || $normalizedFileName === '..') {
                continue;
            }
            $files[$normalizedFileName] = true;
        }
    }

    return [
        'folders' => $folders,
        'files' => array_values(array_keys($files)),
    ];
}

function girffonCustomDesignStructurePayload(array $node): array
{
    $folders = [];
    foreach (($node['folders'] ?? []) as $name => $child) {
        $folders[$name] = girffonCustomDesignStructurePayload(
            is_array($child) ? $child : girffonCustomDesignDefaultStructure()
        );
    }

    return [
        'folders' => $folders ?: new stdClass(),
        'files' => array_values(array_map(static function ($entry): string {
            return (string) $entry;
        }, is_array($node['files'] ?? null) ? $node['files'] : [])),
    ];
}

function girffonCustomDesignReadStructure(): array
{
    $path = girffonCustomDesignStructurePath();
    if (!is_file($path)) {
        return girffonCustomDesignDefaultStructure();
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        return girffonCustomDesignDefaultStructure();
    }

    return girffonCustomDesignNormalizeStructureNode($decoded);
}

function girffonCustomDesignWriteStructure(array $structure): void
{
    $normalized = girffonCustomDesignNormalizeStructureNode($structure);
    $written = file_put_contents(
        girffonCustomDesignStructurePath(),
        json_encode(girffonCustomDesignStructurePayload($normalized), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    );

    if ($written === false) {
        girffonCustomDesignJsonResponse(500, [
            'success' => false,
            'message' => 'Unable to write the folder structure.',
        ]);
    }
}

function girffonCustomDesignEnsureProjectInStructure(array &$structure, array $segments): void
{
    if (!$segments) {
        return;
    }

    $fileName = array_pop($segments);
    $current = &$structure;

    foreach ($segments as $segment) {
        if (!isset($current['folders'][$segment]) || !is_array($current['folders'][$segment])) {
            $current['folders'][$segment] = girffonCustomDesignDefaultStructure();
        }
        $current = &$current['folders'][$segment];
    }

    if (!isset($current['files']) || !is_array($current['files'])) {
        $current['files'] = [];
    }

    if (!in_array($fileName, $current['files'], true)) {
        $current['files'][] = $fileName;
    }
}

function girffonCustomDesignRemoveProjectFromStructure(array &$structure, array $segments): bool
{
    if (!$segments) {
        return false;
    }

    $fileName = array_pop($segments);
    $current = &$structure;

    foreach ($segments as $segment) {
        if (!isset($current['folders'][$segment]) || !is_array($current['folders'][$segment])) {
            return false;
        }
        $current = &$current['folders'][$segment];
    }

    if (!isset($current['files']) || !is_array($current['files'])) {
        return false;
    }

    $nextFiles = array_values(array_filter($current['files'], static function ($entry) use ($fileName): bool {
        return (string) $entry !== $fileName;
    }));

    if (count($nextFiles) === count($current['files'])) {
        return false;
    }

    $current['files'] = $nextFiles;
    return true;
}

function girffonCustomDesignDeleteFolderFromStructure(array &$structure, array $segments): bool
{
    if (!$segments) {
        return false;
    }

    $folderName = array_pop($segments);
    $current = &$structure;

    foreach ($segments as $segment) {
        if (!isset($current['folders'][$segment]) || !is_array($current['folders'][$segment])) {
            return false;
        }
        $current = &$current['folders'][$segment];
    }

    if (!isset($current['folders'][$folderName])) {
        return false;
    }

    unset($current['folders'][$folderName]);
    return true;
}

function girffonCustomDesignProjectFilePath(array $segments, bool $createDirectory = false): string
{
    if (!$segments) {
        girffonCustomDesignJsonResponse(422, [
            'success' => false,
            'message' => 'Project path is required.',
        ]);
    }

    $encodedSegments = array_map('rawurlencode', $segments);
    $fileName = array_pop($encodedSegments) . '.json';
    $directory = girffonCustomDesignBaseDirectory() . DIRECTORY_SEPARATOR . 'projects';

    if ($encodedSegments) {
        $directory .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $encodedSegments);
    }

    if ($createDirectory && !is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        girffonCustomDesignJsonResponse(500, [
            'success' => false,
            'message' => 'Unable to create the project directory.',
        ]);
    }

    return $directory . DIRECTORY_SEPARATOR . $fileName;
}

function girffonCustomDesignDeleteTree(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        @unlink($path);
        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        girffonCustomDesignDeleteTree($path . DIRECTORY_SEPARATOR . $entry);
    }

    @rmdir($path);
}

$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$request = girffonCustomDesignRequestData();
$action = trim((string) ($request['action'] ?? ($_GET['action'] ?? ($requestMethod === 'GET' ? 'structure' : ''))));

if ($requestMethod !== 'GET' && $action === '') {
    girffonCustomDesignJsonResponse(400, [
        'success' => false,
        'available' => true,
        'message' => 'Missing custom design action in the request.',
    ]);
}

if ($action === 'structure') {
    girffonCustomDesignJsonResponse(200, [
        'success' => true,
        'available' => true,
        'structure' => girffonCustomDesignReadStructure(),
    ]);
}

if ($action === 'load') {
    $segments = girffonCustomDesignNormalizePath((string) ($request['path'] ?? ($_GET['path'] ?? '')));
    $projectPath = girffonCustomDesignProjectFilePath($segments, false);
    if (!is_file($projectPath)) {
        girffonCustomDesignJsonResponse(404, [
            'success' => false,
            'available' => true,
            'message' => 'Project not found on the server.',
        ]);
    }

    $decoded = json_decode((string) file_get_contents($projectPath), true);
    if (!is_array($decoded)) {
        girffonCustomDesignJsonResponse(500, [
            'success' => false,
            'available' => true,
            'message' => 'Saved project data is invalid.',
        ]);
    }

    girffonCustomDesignJsonResponse(200, [
        'success' => true,
        'available' => true,
        'data' => $decoded,
        'structure' => girffonCustomDesignReadStructure(),
    ]);
}

if ($action === 'save') {
    $segments = girffonCustomDesignNormalizePath((string) ($request['path'] ?? ''));
    $data = $request['data'] ?? null;
    if (!is_array($data)) {
        girffonCustomDesignJsonResponse(422, [
            'success' => false,
            'available' => true,
            'message' => 'Project data is required.',
        ]);
    }

    $projectPath = girffonCustomDesignProjectFilePath($segments, true);
    $written = file_put_contents($projectPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    if ($written === false) {
        girffonCustomDesignJsonResponse(500, [
            'success' => false,
            'available' => true,
            'message' => 'Unable to save the project on the server.',
        ]);
    }

    $structure = girffonCustomDesignReadStructure();
    girffonCustomDesignEnsureProjectInStructure($structure, $segments);
    girffonCustomDesignWriteStructure($structure);

    girffonCustomDesignJsonResponse(200, [
        'success' => true,
        'available' => true,
        'message' => 'Project saved on the server.',
        'structure' => $structure,
    ]);
}

if ($action === 'save-structure') {
    $structure = $request['structure'] ?? null;
    if (!is_array($structure)) {
        girffonCustomDesignJsonResponse(422, [
            'success' => false,
            'available' => true,
            'message' => 'Folder structure payload is required.',
        ]);
    }

    girffonCustomDesignWriteStructure($structure);
    girffonCustomDesignJsonResponse(200, [
        'success' => true,
        'available' => true,
        'message' => 'Folder structure saved.',
        'structure' => girffonCustomDesignReadStructure(),
    ]);
}

if ($action === 'delete-project') {
    $segments = girffonCustomDesignNormalizePath((string) ($request['path'] ?? ''));
    $projectPath = girffonCustomDesignProjectFilePath($segments, false);
    if (is_file($projectPath) && !@unlink($projectPath)) {
        girffonCustomDesignJsonResponse(500, [
            'success' => false,
            'available' => true,
            'message' => 'Unable to delete the project on the server.',
        ]);
    }

    $structure = girffonCustomDesignReadStructure();
    girffonCustomDesignRemoveProjectFromStructure($structure, $segments);
    girffonCustomDesignWriteStructure($structure);

    girffonCustomDesignJsonResponse(200, [
        'success' => true,
        'available' => true,
        'message' => 'Project deleted.',
        'structure' => $structure,
    ]);
}

if ($action === 'delete-folder') {
    $segments = girffonCustomDesignNormalizePath((string) ($request['path'] ?? ''));
    if (!$segments) {
        girffonCustomDesignJsonResponse(422, [
            'success' => false,
            'available' => true,
            'message' => 'Folder path is required.',
        ]);
    }

    $encodedSegments = array_map('rawurlencode', $segments);
    $folderPath = girffonCustomDesignBaseDirectory() . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $encodedSegments);
    girffonCustomDesignDeleteTree($folderPath);

    $structure = girffonCustomDesignReadStructure();
    girffonCustomDesignDeleteFolderFromStructure($structure, $segments);
    girffonCustomDesignWriteStructure($structure);

    girffonCustomDesignJsonResponse(200, [
        'success' => true,
        'available' => true,
        'message' => 'Folder deleted.',
        'structure' => $structure,
    ]);
}

girffonCustomDesignJsonResponse(400, [
    'success' => false,
    'available' => true,
    'message' => 'Unknown custom design action.',
]);