<?php

namespace App\Livewire\Settings;

use App\Models\DesktopApplication;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DesktopApplicationSettings extends Component
{
    use LivewireAlert;
    public $windows_file_path = '';
    public $mac_file_path = '';
    public $partner_app_ios = '';
    public $partner_app_android = '';
    public $desktopApp;
    public $subtab = 'desktop';

    protected $rules = [
        'windows_file_path' => 'nullable|string|url',
        'mac_file_path' => 'nullable|string|url',
        'partner_app_ios' => 'nullable|string|url',
        'partner_app_android' => 'nullable|string|url',
    ];

    public function mount()
    {
        $this->subtab = request('subtab', 'desktop');
        $this->desktopApp = DesktopApplication::first();
        $this->loadExistingData();
    }

    public function loadExistingData()
    {
        $this->desktopApp = DesktopApplication::first();

        if ($this->desktopApp) {
            $this->windows_file_path = $this->desktopApp->windows_file_path ?? DesktopApplication::WINDOWS_FILE_PATH;
            $this->mac_file_path = $this->desktopApp->mac_file_path ?? DesktopApplication::MAC_FILE_PATH;
            $this->partner_app_ios = $this->desktopApp->partner_app_ios ?? DesktopApplication::PARTNER_APP_IOS_URL;
            $this->partner_app_android = $this->desktopApp->partner_app_android ?? DesktopApplication::PARTNER_APP_ANDROID_URL;
        }
    }

    public function saveAll()
    {
        $this->validate();
        $app = DesktopApplication::first();

        if (!$app) {
            $app = DesktopApplication::create([]);
        }

        $app->windows_file_path = $this->windows_file_path;
        $app->mac_file_path = $this->mac_file_path;
        $app->partner_app_ios = $this->partner_app_ios ?: null;
        $app->partner_app_android = $this->partner_app_android ?: null;
        $app->save();

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
        $this->loadExistingData();
    }

    public function resetWindowsUrl()
    {
        $this->windows_file_path = DesktopApplication::WINDOWS_FILE_PATH;
    }

    public function resetMacUrl()
    {
        $this->mac_file_path = DesktopApplication::MAC_FILE_PATH;
    }

    public function resetPartnerAppIosUrl()
    {
        $this->partner_app_ios = DesktopApplication::PARTNER_APP_IOS_URL ?: '';
    }

    public function resetPartnerAppAndroidUrl()
    {
        $this->partner_app_android = DesktopApplication::PARTNER_APP_ANDROID_URL ?: '';
    }

    public function render()
    {
        $desktopApplication = DesktopApplication::first();
        $subtab = request('subtab', 'desktop');
        return view('livewire.settings.desktop-application-settings', compact('desktopApplication', 'subtab'));
    }
}
