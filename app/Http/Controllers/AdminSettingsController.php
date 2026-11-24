<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminSettingsController extends Controller
{
    public function index(): View
    {
        $audioPath = Setting::get('admin.alarm_audio_path');
        $commandCenterLogoPath = Setting::get('admin.command_center_logo_path');

        return view('admin.settings', [
            'hasAlarmAudio' => is_string($audioPath) && Storage::disk('local')->exists($audioPath),
            'hasCommandCenterLogo' => is_string($commandCenterLogoPath) && Storage::disk('local')->exists($commandCenterLogoPath),
            'commandCenterLocation' => Setting::get('admin.command_center_location', [
                'mode' => 'manual',
                'latitude' => 11.7061,
                'longitude' => 122.3687,
            ]),
            'reportSettings' => [
                'description_min_length' => Setting::get('report.description_min_length', 20),
                'description_max_length' => Setting::get('report.description_max_length', 2000),
                'reporter_name_max_length' => Setting::get('report.reporter_name_max_length', 120),
                'expiry_days' => Setting::get('report.expiry_days', 7),
            ],
        ]);
    }

    public function updateReportSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'description_min_length' => ['required', 'integer', 'min:1', 'max:500'],
            'description_max_length' => ['required', 'integer', 'gte:description_min_length', 'max:5000'],
            'reporter_name_max_length' => ['required', 'integer', 'min:1', 'max:200'],
            'expiry_days' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put('report.' . $key, (int) $value);
        }

        return redirect()->route('admin.settings')->with('status', 'Report intake settings saved.');
    }

    public function updateCommandCenterLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_mode' => ['required', 'in:automatic,manual'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        Setting::put('admin.command_center_location', [
            'mode' => $data['location_mode'],
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
        ]);

        return redirect()->route('admin.settings')->with('status', 'Command center location saved.');
    }

    public function storeCommandCenterLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'command_center_logo' => ['required', 'image', 'mimes:jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'command_center_logo.image' => 'Choose a valid image file.',
            'command_center_logo.mimes' => 'Use a JPEG, PNG, or WebP image. HEIC/HEIF photos are not supported; export or convert them first.',
            'command_center_logo.extensions' => 'Use a .jpg, .jpeg, .png, or .webp image.',
            'command_center_logo.max' => 'The image must be 2048 KB (2 MB) or smaller.',
        ]);

        $disk = Storage::disk('local');
        $previousPath = Setting::get('admin.command_center_logo_path');
        $path = $disk->putFile('admin/command-center', $request->file('command_center_logo'));

        if (! $path) {
            throw ValidationException::withMessages(['command_center_logo' => 'The Command Center logo could not be stored.']);
        }

        Setting::put('admin.command_center_logo_path', $path);

        if (is_string($previousPath) && $previousPath !== $path) {
            $disk->delete($previousPath);
        }

        return redirect()->route('admin.settings')->with('status', 'Command Center logo uploaded.');
    }

    public function commandCenterLogo(): BinaryFileResponse
    {
        $path = Setting::get('admin.command_center_logo_path');
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function removeCommandCenterLogo(): RedirectResponse
    {
        $path = Setting::get('admin.command_center_logo_path');
        if (is_string($path)) {
            Storage::disk('local')->delete($path);
        }
        Setting::query()->whereKey('admin.command_center_logo_path')->delete();
        Setting::forget('admin.command_center_logo_path');

        return redirect()->route('admin.settings')->with('status', 'Custom Command Center logo removed. The SOU logo will be used.');
    }

    public function storeAlarmAudio(Request $request): RedirectResponse
    {
        $request->validate([
            'alarm_audio' => [
                'required',
                'file',
                'max:5120',
                'extensions:mp3',
                function (string $attribute, mixed $file, \Closure $fail): void {
                    if (! $file || ! $file->isValid()) {
                        return;
                    }

                    $handle = fopen($file->getRealPath(), 'rb');
                    $header = $handle ? fread($handle, 4) : '';
                    if ($handle) {
                        fclose($handle);
                    }

                    $hasId3Header = str_starts_with($header, 'ID3');
                    $hasMpegFrame = strlen($header) >= 2
                        && ord($header[0]) === 0xFF
                        && (ord($header[1]) & 0xE0) === 0xE0;

                    if (! $hasId3Header && ! $hasMpegFrame) {
                        $fail('Upload a valid MP3 audio file.');
                    }
                },
            ],
        ]);

        $disk = Storage::disk('local');
        $previousPath = Setting::get('admin.alarm_audio_path');
        $path = $disk->putFile('admin/alarm', $request->file('alarm_audio'));

        if (! $path) {
            throw ValidationException::withMessages(['alarm_audio' => 'The alarm file could not be stored.']);
        }

        Setting::put('admin.alarm_audio_path', $path);

        if (is_string($previousPath) && $previousPath !== $path) {
            $disk->delete($previousPath);
        }

        return redirect()->route('admin.settings')->with('status', 'Alarm sound uploaded.');
    }

    public function alarmAudio(): BinaryFileResponse
    {
        $path = Setting::get('admin.alarm_audio_path');
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=60',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function removeAlarmAudio(): RedirectResponse
    {
        $path = Setting::get('admin.alarm_audio_path');
        if (is_string($path)) {
            Storage::disk('local')->delete($path);
        }
        Setting::query()->whereKey('admin.alarm_audio_path')->delete();
        Setting::forget('admin.alarm_audio_path');

        return redirect()->route('admin.settings')->with('status', 'Custom alarm removed. The built-in alarm will be used.');
    }
}