<?php

namespace App\Controllers\Api;

use App\Core\Controller;

class VersionController extends Controller
{
    /**
     * Get current published version (public endpoint for update checks)
     */
    public function current(): array
    {
        try {
            // Get the latest published version from database
            $version = $this->db->fetch(
                "SELECT * FROM version_releases 
                 WHERE is_published = TRUE 
                 ORDER BY id DESC 
                 LIMIT 1"
            );

            if (!$version) {
                // Fallback to version.json if no database entry
                $versionFile = ROOT_PATH . '/version.json';
                if (file_exists($versionFile)) {
                    $data = json_decode(file_get_contents($versionFile), true);
                    $data['_source'] = 'version.json (no db record)';
                    return $this->success($data);
                }

                return $this->success([
                    'version' => '1.0.0',
                    'build' => date('Ymd'),
                    'changelog' => [],
                    '_source' => 'default (no db, no file)'
                ]);
            }

            // Format response from database
            $changelog = [];
            if ($version['features'] || $version['fixes'] || $version['improvements']) {
                $changelog[] = [
                    'version' => $version['version'],
                    'date' => $version['published_at'] ? date('Y-m-d', strtotime($version['published_at'])) : null,
                    'features' => json_decode($version['features'] ?? '[]', true),
                    'fixes' => json_decode($version['fixes'] ?? '[]', true),
                    'improvements' => json_decode($version['improvements'] ?? '[]', true)
                ];
            }

            return $this->success([
                'version' => $version['version'],
                'build' => $version['build'] ?? date('Ymd'),
                'name' => $version['name'] ?? 'BuildFlow ERP',
                'releaseDate' => $version['published_at'] ? date('Y-m-d', strtotime($version['published_at'])) : null,
                'forceUpdate' => (bool) $version['force_update'],
                'forceUpdateMessage' => $version['force_update_message'],
                'changelog' => $changelog,
                '_source' => 'database'
            ]);

        } catch (\Exception $e) {
            // Fallback to version.json on any error
            $versionFile = ROOT_PATH . '/version.json';
            if (file_exists($versionFile)) {
                $data = json_decode(file_get_contents($versionFile), true);
                $data['_source'] = 'version.json (db error: ' . $e->getMessage() . ')';
                return $this->success($data);
            }

            return $this->success([
                'version' => '1.0.0',
                'build' => date('Ymd'),
                '_source' => 'default (db error: ' . $e->getMessage() . ')'
            ]);
        }
    }

    /**
     * List all releases (dev admin only)
     */
    public function index(): array
    {
        // Verify dev session
        if (!$this->isDevAdmin()) {
            return $this->error('Unauthorized', 401);
        }

        try {
            $releases = $this->db->fetchAll(
                "SELECT * FROM version_releases ORDER BY created_at DESC"
            );

            // Get stats
            $stats = [
                'total' => count($releases),
                'published' => count(array_filter($releases, fn($r) => $r['is_published'])),
                'drafts' => count(array_filter($releases, fn($r) => !$r['is_published'])),
                'current_version' => null
            ];

            // Get current published version
            $current = array_filter($releases, fn($r) => $r['is_published']);
            if (!empty($current)) {
                usort($current, fn($a, $b) => strtotime($b['published_at'] ?? 0) - strtotime($a['published_at'] ?? 0));
                $stats['current_version'] = reset($current)['version'];
            }

            // Decode JSON fields
            foreach ($releases as &$release) {
                $release['features'] = json_decode($release['features'] ?? '[]', true);
                $release['fixes'] = json_decode($release['fixes'] ?? '[]', true);
                $release['improvements'] = json_decode($release['improvements'] ?? '[]', true);
            }

            return $this->success([
                'releases' => $releases,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            // Table might not exist - suggest running migration
            if (strpos($e->getMessage(), 'version_releases') !== false || strpos($e->getMessage(), "doesn't exist") !== false) {
                return $this->error('Table version_releases not found. Please run migration: database/migrations/013_version_releases.sql', 500);
            }
            return $this->error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new release draft
     */
    public function store(): array
    {
        if (!$this->isDevAdmin()) {
            $this->error('Unauthorized', 401);
        }

        $data = $this->validate([
            'version' => 'required',
        ]);

        $input = $this->getJsonInput();

        // Check if version already exists
        $existing = $this->db->fetch(
            "SELECT id FROM version_releases WHERE version = ?",
            [$data['version']]
        );

        if ($existing) {
            $this->error('Version ' . $data['version'] . ' already exists', 400);
        }

        $releaseId = $this->db->insert('version_releases', [
            'version' => $data['version'],
            'build' => $input['build'] ?? date('Ymd'),
            'name' => $input['name'] ?? null,
            'release_notes' => $input['release_notes'] ?? null,
            'features' => json_encode($input['features'] ?? []),
            'fixes' => json_encode($input['fixes'] ?? []),
            'improvements' => json_encode($input['improvements'] ?? []),
            'is_published' => false,
            'force_update' => $input['force_update'] ?? false,
            'force_update_message' => $input['force_update_message'] ?? null
        ]);

        return $this->success(['id' => $releaseId], 'Release draft created', 201);
    }

    /**
     * Update a release
     */
    public function update(string $id): array
    {
        if (!$this->isDevAdmin()) {
            $this->error('Unauthorized', 401);
        }

        $release = $this->db->fetch(
            "SELECT * FROM version_releases WHERE id = ?",
            [$id]
        );

        if (!$release) {
            $this->error('Release not found', 404);
        }

        $input = $this->getJsonInput();

        $updateData = [];

        if (isset($input['version']))
            $updateData['version'] = $input['version'];
        if (isset($input['build']))
            $updateData['build'] = $input['build'];
        if (isset($input['name']))
            $updateData['name'] = $input['name'];
        if (isset($input['release_notes']))
            $updateData['release_notes'] = $input['release_notes'];
        if (isset($input['features']))
            $updateData['features'] = json_encode($input['features']);
        if (isset($input['fixes']))
            $updateData['fixes'] = json_encode($input['fixes']);
        if (isset($input['improvements']))
            $updateData['improvements'] = json_encode($input['improvements']);
        if (isset($input['force_update']))
            $updateData['force_update'] = $input['force_update'];
        if (isset($input['force_update_message']))
            $updateData['force_update_message'] = $input['force_update_message'];

        if (!empty($updateData)) {
            $this->db->update('version_releases', $updateData, ['id' => $id]);
        }

        return $this->success(null, 'Release updated');
    }

    /**
     * Publish a release - makes it available to all users
     */
    public function publish(string $id): array
    {
        if (!$this->isDevAdmin()) {
            return $this->error('Unauthorized', 401);
        }

        $release = $this->db->fetch(
            "SELECT * FROM version_releases WHERE id = ?",
            [$id]
        );

        if (!$release) {
            return $this->error('Release not found', 404);
        }

        if ($release['is_published']) {
            return $this->error('Release is already published', 400);
        }

        // Get dev user info
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $devUser = $_SESSION['dev_user'] ?? null;

        // Update the release
        $this->db->update('version_releases', [
            'is_published' => true,
            'published_at' => date('Y-m-d H:i:s'),
            'published_by' => $devUser['id'] ?? null
        ], ['id' => $id]);

        // Update version.json file for compatibility
        $this->updateVersionFile($release);

        return $this->success([
            'version' => $release['version'],
            'published_at' => date('Y-m-d H:i:s')
        ], 'Version ' . $release['version'] . ' has been published! Users will be notified on their next page load.');
    }

    /**
     * Quick Release - Auto-increment version and publish immediately
     */
    public function quickRelease(): array
    {
        if (!$this->isDevAdmin()) {
            return $this->error('Unauthorized', 401);
        }

        try {
            // Get current version
            $currentRelease = $this->db->fetch(
                "SELECT version FROM version_releases WHERE is_published = TRUE ORDER BY published_at DESC LIMIT 1"
            );

            $currentVersion = $currentRelease['version'] ?? '1.0.0';

            // Get next version from request or auto-increment
            $input = $this->getJsonInput();
            $nextVersion = $input['next_version'] ?? $this->incrementVersion($currentVersion);

            // Check if version already exists
            $existing = $this->db->fetch(
                "SELECT id FROM version_releases WHERE version = ?",
                [$nextVersion]
            );

            if ($existing) {
                return $this->error('Version ' . $nextVersion . ' already exists', 400);
            }

            // Get dev user info
            if (session_status() === PHP_SESSION_NONE)
                session_start();
            $devUser = $_SESSION['dev_user'] ?? null;

            // Create and publish in one step
            $releaseId = $this->db->insert('version_releases', [
                'version' => $nextVersion,
                'build' => date('Ymd'),
                'name' => 'Update ' . date('M d, Y'),
                'release_notes' => 'System update',
                'features' => json_encode(['Bug fixes and improvements']),
                'fixes' => json_encode([]),
                'improvements' => json_encode([]),
                'is_published' => true,
                'published_at' => date('Y-m-d H:i:s'),
                'published_by' => $devUser['id'] ?? null,
                'force_update' => false
            ]);

            // Get the created release for updating version.json
            $release = $this->db->fetch("SELECT * FROM version_releases WHERE id = ?", [$releaseId]);

            // Update version.json file
            $this->updateVersionFile($release);

            return $this->success([
                'id' => $releaseId,
                'version' => $nextVersion,
                'published_at' => date('Y-m-d H:i:s')
            ], 'Version ' . $nextVersion . ' released! Users will be notified.');

        } catch (\Exception $e) {
            return $this->error('Failed to create quick release: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Increment version number (patch level)
     */
    private function incrementVersion(string $version): string
    {
        $parts = array_map('intval', explode('.', $version));

        // Ensure at least 3 parts
        while (count($parts) < 3) {
            $parts[] = 0;
        }

        // Increment patch version
        $parts[2]++;

        return implode('.', $parts);
    }

    /**
     * Delete a release (drafts only)
     */
    public function destroy(string $id): array
    {
        if (!$this->isDevAdmin()) {
            $this->error('Unauthorized', 401);
        }

        $release = $this->db->fetch(
            "SELECT * FROM version_releases WHERE id = ?",
            [$id]
        );

        if (!$release) {
            $this->error('Release not found', 404);
        }

        if ($release['is_published']) {
            $this->error('Cannot delete a published release', 400);
        }

        $this->db->delete('version_releases', ['id' => $id]);

        return $this->success(null, 'Release deleted');
    }

    /**
     * Update the version.json file for compatibility with update-service.js
     */
    private function updateVersionFile(array $release): void
    {
        $versionFile = ROOT_PATH . '/version.json';

        $data = [
            'version' => $release['version'],
            'build' => $release['build'] ?? date('Ymd'),
            'name' => $release['name'] ?? 'BuildFlow ERP',
            'releaseDate' => date('Y-m-d'),
            'minSupportedVersion' => '1.0.0',
            'changelog' => [
                [
                    'version' => $release['version'],
                    'date' => date('Y-m-d'),
                    'features' => json_decode($release['features'] ?? '[]', true),
                    'fixes' => json_decode($release['fixes'] ?? '[]', true),
                    'improvements' => json_decode($release['improvements'] ?? '[]', true)
                ]
            ],
            'forceUpdate' => (bool) $release['force_update'],
            'forceUpdateMessage' => $release['force_update_message']
        ];

        file_put_contents($versionFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Check if current request is from a dev admin
     */
    private function isDevAdmin(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['dev_user']);
    }
}
