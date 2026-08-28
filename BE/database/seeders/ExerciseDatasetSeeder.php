<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\BaiTapDungCu;
use App\Models\BaiTapNhomCo;
use App\Models\DungCu;
use App\Models\NhomCo;
use App\Models\NguoiDung;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Imports the approved external exercise dataset into the existing library tables.
 *
 * The source is deliberately not downloaded by a normal db:seed invocation. Set
 * EXERCISE_DATASET_PATH to override the project-local .tmp source directory.
 */
class ExerciseDatasetSeeder extends Seeder
{
    use WithoutModelEvents;

    private const DEFAULT_DIFFICULTY = 'CHUA_XAC_DINH';
    private const DATASET_RELATIVE_PATH = '.tmp/exercises-dataset';

    /** @var array<int, string> */
    private array $copiedFiles = [];

    public function run(): void
    {
        $sourcePath = $this->sourcePath();
        $records = $this->readRecords($sourcePath);
        $admin = NguoiDung::query()
            ->where('thu_dien_tu', 'dev.admin@smartfitness.local')
            ->first();

        if (!$admin) {
            throw new \RuntimeException('EXERCISE DATASET SEEDER REQUIRES dev.admin@smartfitness.local. Run DemoNguoiDungSeeder first.');
        }

        try {
            $summary = DB::transaction(fn (): array => $this->importRecords($records, $admin->id, $sourcePath));
        } catch (\Throwable $exception) {
            foreach ($this->copiedFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            throw $exception;
        } finally {
            $this->copiedFiles = [];
        }

        if ($this->command) {
            $this->command->info(sprintf(
                'Exercise dataset: %d exercises, %d equipment, %d muscle groups, %d media files (%d missing).',
                $summary['exercises'],
                $summary['equipment'],
                $summary['muscle_groups'],
                $summary['media_copied'],
                $summary['media_missing'],
            ));
        }
    }

    /**
     * Public entry point used by isolated seeder tests with fixture records.
     * @param array<int, array<string, mixed>> $records
     * @return array<string, int>
     */
    public function importFromRecords(array $records, int $adminId, ?string $sourcePath = null): array
    {
        return DB::transaction(fn (): array => $this->importRecords($records, $adminId, $sourcePath));
    }

    /** Return a stable case/whitespace-insensitive lookup key. */
    public function normalizeForLookup(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        return (string) preg_replace('/\s+/', ' ', $value);
    }

    public function sourcePath(): string
    {
        $configured = trim((string) env('EXERCISE_DATASET_PATH', ''));
        if ($configured !== '') {
            $isAbsolute = (bool) preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2})/', $configured);
            $candidate = $isAbsolute ? $configured : base_path($configured);
        } else {
            $candidate = dirname(base_path()) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::DATASET_RELATIVE_PATH);
        }
        $resolved = realpath($candidate);
        if ($resolved === false || !is_dir($resolved)) {
            throw new \RuntimeException('EXERCISE DATASET SOURCE NOT FOUND. Expected EXERCISE_DATASET_PATH or project-local .tmp/exercises-dataset.');
        }
        return $resolved;
    }

    /** @return array<int, array<string, mixed>> */
    private function readRecords(string $sourcePath): array
    {
        $file = $sourcePath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'exercises.json';
        if (!is_file($file) || filesize($file) < 1) {
            throw new \RuntimeException("EXERCISE DATASET FILE NOT FOUND: {$file}");
        }
        $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \RuntimeException('EXERCISE DATASET JSON MUST BE AN ARRAY.');
        }
        return array_values(array_filter($data, static fn ($record): bool => is_array($record)));
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array<string, int>
     */
    private function importRecords(array $records, int $adminId, ?string $sourcePath): array
    {
        $equipmentValues = [];
        $muscleValues = [];
        foreach ($records as $record) {
            $equipment = trim((string) ($record['equipment'] ?? ''));
            if ($equipment !== '') {
                $equipmentValues[$this->normalizeForLookup($equipment)] = $equipment;
            }
            foreach ($this->muscleValues($record) as $muscle) {
                $muscleValues[$this->normalizeForLookup($muscle)] = $muscle;
            }
        }

        $equipmentMap = [];
        foreach ($equipmentValues as $lookup => $name) {
            $equipmentMap[$lookup] = $this->upsertEquipment($name);
        }
        $muscleMap = [];
        foreach ($muscleValues as $lookup => $name) {
            $muscleMap[$lookup] = $this->upsertMuscle($name);
        }

        $sourceCommit = $this->sourceCommit($sourcePath);
        $exerciseCount = 0;
        $mediaCopied = 0;
        $mediaMissing = 0;
        foreach ($records as $record) {
            $externalId = trim((string) ($record['id'] ?? ''));
            $name = trim((string) ($record['name'] ?? ''));
            if ($externalId === '' || $name === '') {
                continue;
            }

            $code = $this->exerciseCode($externalId);
            $existing = BaiTap::query()->where('ma_bai_tap', $code)->first();
            $media = $this->copyMedia($record, $sourcePath);
            $mediaCopied += $media['copied'];
            $mediaMissing += $media['missing'];

            $metadata = [
                'external_source' => 'https://github.com/hasaneyldrm/exercises-dataset',
                'external_id' => $externalId,
                'source_commit' => $sourceCommit,
                'source_name' => $name,
                'source_category' => trim((string) ($record['category'] ?? '')),
                'source_body_part' => trim((string) ($record['body_part'] ?? '')),
                'source_equipment' => trim((string) ($record['equipment'] ?? '')),
                'source_target' => trim((string) ($record['target'] ?? '')),
                'source_muscle_group' => trim((string) ($record['muscle_group'] ?? '')),
                'source_secondary_muscles' => array_values(array_map('strval', (array) ($record['secondary_muscles'] ?? []))),
                'instruction_languages' => array_keys((array) ($record['instructions'] ?? [])),
                'media_id' => trim((string) ($record['media_id'] ?? '')),
                'media_source' => 'Gym visual',
                'attribution' => trim((string) ($record['attribution'] ?? '© Gym visual — https://gymvisual.com/')),
            ];
            $oldMetadata = is_array($existing?->thong_tin_bo_sung) ? $existing->thong_tin_bo_sung : [];
            $metadata = array_merge($oldMetadata, $metadata);

            $exercise = $existing ?: new BaiTap();
            $exercise->ma_bai_tap = $code;
            $exercise->ten_bai_tap = $name;
            $exercise->do_kho = self::DEFAULT_DIFFICULTY;
            $exercise->huong_dan = $this->instruction($record);
            if ($media['image_path'] !== null) {
                $exercise->duong_dan_hinh_anh = $media['image_path'];
            }
            if ($media['video_path'] !== null) {
                $exercise->duong_dan_video = $media['video_path'];
            }
            $exercise->thong_tin_bo_sung = $metadata;
            $exercise->phien_ban_noi_dung = $existing?->phien_ban_noi_dung ?: 1;
            $exercise->trang_thai = $existing?->trang_thai ?: 'HOAT_DONG';
            $exercise->nguoi_tao_id = $existing?->nguoi_tao_id ?: $adminId;
            $exercise->save();

            $equipment = trim((string) ($record['equipment'] ?? ''));
            if ($equipment !== '' && isset($equipmentMap[$this->normalizeForLookup($equipment)])) {
                BaiTapDungCu::query()->firstOrCreate([
                    'bai_tap_id' => $exercise->id,
                    'dung_cu_id' => $equipmentMap[$this->normalizeForLookup($equipment)],
                ]);
            }
            $primary = $this->primaryMuscle($record);
            foreach ($this->muscleValues($record) as $muscle) {
                $lookup = $this->normalizeForLookup($muscle);
                if (!isset($muscleMap[$lookup])) {
                    continue;
                }
                BaiTapNhomCo::query()->firstOrCreate(
                    ['bai_tap_id' => $exercise->id, 'nhom_co_id' => $muscleMap[$lookup]],
                    ['vai_tro_nhom_co' => $this->normalizeForLookup($muscle) === $this->normalizeForLookup($primary) ? 'CHINH' : 'PHU'],
                );
            }
            $exerciseCount++;
        }

        return [
            'exercises' => $exerciseCount,
            'equipment' => count($equipmentMap),
            'muscle_groups' => count($muscleMap),
            'media_copied' => $mediaCopied,
            'media_missing' => $mediaMissing,
        ];
    }

    private function upsertEquipment(string $name): int
    {
        $equipment = DungCu::query()->firstOrNew(['ma_dung_cu' => $this->catalogCode('DC', $name)]);
        $equipment->ten_dung_cu = $name;
        $equipment->mo_ta = 'Danh muc dung cu tu Exercise Dataset; khong phai tai san gym.';
        $equipment->trang_thai = 'HOAT_DONG';
        $equipment->save();
        return (int) $equipment->id;
    }

    private function upsertMuscle(string $name): int
    {
        $muscle = NhomCo::query()->firstOrNew(['ma_nhom_co' => $this->catalogCode('NC', $name)]);
        $muscle->ten_nhom_co = $name;
        $muscle->mo_ta = 'Nhom co duoc chuan hoa tu truong muscle/target cua Exercise Dataset.';
        $muscle->save();
        return (int) $muscle->id;
    }

    /** @param array<string, mixed> $record */
    private function instruction(array $record): string
    {
        $instructions = (array) ($record['instructions'] ?? []);
        foreach (['en', 'en-US'] as $language) {
            if (trim((string) ($instructions[$language] ?? '')) !== '') {
                return trim((string) $instructions[$language]);
            }
        }
        foreach ($instructions as $value) {
            if (trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }
        return 'Chua co huong dan tu nguon du lieu.';
    }

    /** @param array<string, mixed> $record */
    private function primaryMuscle(array $record): string
    {
        foreach (['target', 'muscle_group', 'category'] as $field) {
            $value = trim((string) ($record[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
        return 'other';
    }

    /** @param array<string, mixed> $record @return array<int, string> */
    private function muscleValues(array $record): array
    {
        $values = [$this->primaryMuscle($record), trim((string) ($record['muscle_group'] ?? ''))];
        foreach ((array) ($record['secondary_muscles'] ?? []) as $value) {
            $values[] = trim((string) $value);
        }
        $result = [];
        foreach ($values as $value) {
            if ($value !== '' && !isset($result[$this->normalizeForLookup($value)])) {
                $result[$this->normalizeForLookup($value)] = $value;
            }
        }
        return array_values($result);
    }

    /** @param array<string, mixed> $record @return array{image_path:?string,video_path:?string,copied:int,missing:int} */
    private function copyMedia(array $record, ?string $sourcePath): array
    {
        if (!$sourcePath) {
            return ['image_path' => null, 'video_path' => null, 'copied' => 0, 'missing' => 0];
        }
        $result = ['image_path' => null, 'video_path' => null, 'copied' => 0, 'missing' => 0];
        foreach ([['field' => 'image', 'folder' => 'images', 'column' => 'image_path'], ['field' => 'gif_url', 'folder' => 'videos', 'column' => 'video_path']] as $media) {
            $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim((string) ($record[$media['field']] ?? '')));
            $relative = ltrim($relative, DIRECTORY_SEPARATOR);
            if ($relative === '' || !str_starts_with($relative, $media['folder'] . DIRECTORY_SEPARATOR)) {
                $result['missing']++;
                continue;
            }
            $source = realpath($sourcePath . DIRECTORY_SEPARATOR . $relative);
            $approvedRoot = realpath($sourcePath . DIRECTORY_SEPARATOR . $media['folder']);
            $sourcePrefix = $approvedRoot === false ? '' : rtrim($approvedRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ($source === false || $sourcePrefix === '' || !str_starts_with(strtolower($source), strtolower($sourcePrefix)) || !is_file($source) || filesize($source) < 1) {
                $result['missing']++;
                continue;
            }
            $filename = basename($source);
            $targetRelative = 'exercises/' . $media['folder'] . '/' . $filename;
            $target = storage_path('app/public/' . $targetRelative);
            File::ensureDirectoryExists(dirname($target));
            $targetExisted = is_file($target);
            if (!copy($source, $target)) {
                throw new \RuntimeException("Unable to copy exercise media: {$source}");
            }
            if (!$targetExisted && !in_array($target, $this->copiedFiles, true)) {
                $this->copiedFiles[] = $target;
            }
            $result[$media['column']] = $targetRelative;
            $result['copied']++;
        }
        return $result;
    }

    private function exerciseCode(string $externalId): string
    {
        return $this->catalogCode('EX', $externalId, 40);
    }

    private function catalogCode(string $prefix, string $value, int $maxLength = 40): string
    {
        $ascii = strtoupper(Str::ascii(trim($value)));
        $slug = (string) preg_replace('/[^A-Z0-9]+/', '_', $ascii);
        $slug = trim($slug, '_');
        $code = $prefix . '_' . ($slug !== '' ? $slug : 'OTHER');
        if (strlen($code) <= $maxLength) {
            return $code;
        }
        return substr($code, 0, $maxLength - 7) . '_' . substr(sha1($this->normalizeForLookup($value)), 0, 6);
    }

    private function sourceCommit(?string $sourcePath): ?string
    {
        $configured = trim((string) env('EXERCISE_DATASET_COMMIT', ''));
        if ($configured !== '') {
            return $configured;
        }
        if (!$sourcePath || !is_dir($sourcePath . DIRECTORY_SEPARATOR . '.git')) {
            return null;
        }
        $command = 'git -C ' . escapeshellarg($sourcePath) . ' rev-parse HEAD 2>&1';
        $commit = trim((string) @shell_exec($command));
        return preg_match('/^[0-9a-f]{40}$/i', $commit) ? $commit : null;
    }
}
