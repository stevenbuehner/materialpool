<?php

namespace App\Services\MaterialHandling;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use RuntimeException;

class MaterialDownloadStore {
    public function root(): string {
        return config('material_downloads.root');
    }

    public function readyPath(string $token): string {
        return $this->root().'/ready/'.$token.'.zip';
    }

    public function workPath(string $token): string {
        return $this->root().'/work/'.$token.'.zip';
    }

    public function url(string $token): string {
        return '/material-downloads/'.$token.'.zip';
    }

    public function create(string $token, int $materialId, int $userId, string $until): void {
        $this->save($token, [
            'material_id' => $materialId,
            'user_id' => $userId,
            'until' => $until,
            'status' => 'pending',
        ]);
    }

    public function read(string $token): ?array {
        $path = $this->statusPath($token);
        if (!File::exists($path)) {
            return NULL;
        }

        $data = json_decode(File::get($path), TRUE);
        return is_array($data) ? $data : NULL;
    }

    public function setStatus(string $token, string $status): void {
        $data = $this->read($token);
        if ($data === NULL) {
            throw new RuntimeException('Material download status is missing.');
        }
        $data['status'] = $status;
        $this->save($token, $data);
    }

    public function publish(string $token): void {
        $data = $this->read($token);
        if ($data === NULL || Carbon::parse($data['until'])->isPast()) {
            throw new RuntimeException('Material download has expired.');
        }
        File::ensureDirectoryExists($this->root().'/ready');
        if (!rename($this->workPath($token), $this->readyPath($token))) {
            throw new RuntimeException('Could not publish material download.');
        }
        $this->setStatus($token, 'ready');
    }

    public function delete(string $token): void {
        File::delete([$this->readyPath($token), $this->workPath($token), $this->statusPath($token)]);
    }

    public function deleteExpired(): void {
        foreach (File::glob($this->root().'/status/*.json') ?: [] as $path) {
            $token = basename($path, '.json');
            $data = $this->read($token);
            if ($data !== NULL && isset($data['until']) && Carbon::parse($data['until'])->isPast()) {
                $this->delete($token);
            }
        }
    }

    private function statusPath(string $token): string {
        return $this->root().'/status/'.$token.'.json';
    }

    private function save(string $token, array $data): void {
        File::ensureDirectoryExists($this->root().'/status');
        $path = $this->statusPath($token);
        $temp = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        if (File::put($temp, json_encode($data, JSON_THROW_ON_ERROR)) === FALSE || !rename($temp, $path)) {
            File::delete($temp);
            throw new RuntimeException('Could not save material download status.');
        }
    }
}
