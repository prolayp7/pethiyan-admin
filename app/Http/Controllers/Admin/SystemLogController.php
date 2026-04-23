<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    private const MAX_PREVIEW_LINES = 250;
    private const MAX_PREVIEW_BYTES = 131072;

    public function index(Request $request): View
    {
        $this->authorizeView();

        $logDirectory = storage_path('logs');
        $files = $this->getLogFiles($logDirectory);
        $selectedFile = $this->resolveSelectedFile($request->query('file'), $files);

        return view('admin.settings.logs', [
            'logFiles' => $files,
            'selectedFile' => $selectedFile,
            'selectedFileName' => $selectedFile?->getFilename(),
            'selectedFileStats' => $selectedFile ? $this->buildFileStats($selectedFile) : null,
            'logPreview' => $selectedFile ? $this->tailFile($selectedFile->getPathname()) : [],
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->authorizeClear();

        $validated = $request->validate([
            'file' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $admin = Auth::guard('admin')->user();
        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return back()
                ->withErrors(['password' => 'Invalid password.'])
                ->withInput($request->except('password'));
        }

        $logDirectory = storage_path('logs');
        $files = $this->getLogFiles($logDirectory);
        $selectedFile = $this->resolveSelectedFile($validated['file'], $files);

        if (!$selectedFile) {
            abort(404, 'Log file not found.');
        }

        File::put($selectedFile->getPathname(), '');

        return redirect()
            ->route('admin.system-logs.index', ['file' => $selectedFile->getFilename()])
            ->with('success', 'Log file cleared successfully.');
    }

    private function authorizeView(): void
    {
        $user = Auth::guard('admin')->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return;
        }

        if (!$user->hasPermissionTo(AdminPermissionEnum::SETTING_SYSTEM_VIEW->value)) {
            throw new AuthorizationException(__('labels.unauthorized_access'));
        }
    }

    private function authorizeClear(): void
    {
        $user = Auth::guard('admin')->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return;
        }

        if (!$user->hasPermissionTo(AdminPermissionEnum::SETTING_SYSTEM_EDIT->value)) {
            throw new AuthorizationException(__('labels.unauthorized_access'));
        }
    }

    /**
     * @return Collection<int, \SplFileInfo>
     */
    private function getLogFiles(string $logDirectory): Collection
    {
        if (!File::isDirectory($logDirectory)) {
            return collect();
        }

        return collect(File::files($logDirectory))
            ->filter(fn(\SplFileInfo $file) => strtolower($file->getExtension()) === 'log')
            ->sortByDesc(fn(\SplFileInfo $file) => $file->getMTime())
            ->values();
    }

    private function resolveSelectedFile(?string $requestedFile, Collection $files): ?\SplFileInfo
    {
        if ($files->isEmpty()) {
            return null;
        }

        if (!$requestedFile) {
            return $files->first();
        }

        return $files->first(fn(\SplFileInfo $file) => $file->getFilename() === basename($requestedFile));
    }

    /**
     * @return array{size_human:string,size_bytes:int,modified_at:string,error_count:int,warning_count:int}
     */
    private function buildFileStats(\SplFileInfo $file): array
    {
        $preview = $this->tailFile($file->getPathname());

        $errorCount = collect($preview)->filter(fn(string $line) => str_contains(strtolower($line), '.error:'))->count();
        $warningCount = collect($preview)->filter(fn(string $line) => str_contains(strtolower($line), '.warning:'))->count();

        return [
            'size_human' => $this->formatBytes($file->getSize()),
            'size_bytes' => $file->getSize(),
            'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function tailFile(string $path): array
    {
        try {
            $size = File::size($path);
        } catch (FileNotFoundException) {
            return [];
        }

        if ($size <= 0) {
            return [];
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $readBytes = min($size, self::MAX_PREVIEW_BYTES);
        fseek($handle, -$readBytes, SEEK_END);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        $content = ltrim($content, "\n\r");
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];

        return array_slice($lines, -self::MAX_PREVIEW_LINES);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 2) . ' ' . $units[$power];
    }
}
