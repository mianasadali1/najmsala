<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;
use Dipokhalder\EnvEditor\EnvEditor;
use Dipokhalder\Settings\Facades\Settings;

class NotificationTableSeeder extends Seeder
{
    public function run()
    {
        $envService = new EnvEditor();

        // Security note: keep real Firebase credentials out of git.
        // For demo environments we use non-secret placeholders; real values should be configured in env/admin.
        Settings::group('notification')->set([
            'notification_fcm_public_vapid_key'    => $envService->getValue('DEMO') ? 'DEMO_PUBLIC_VAPID_KEY' : '',
            'notification_fcm_api_key'             => $envService->getValue('DEMO') ? 'DEMO_FCM_API_KEY' : '',
            'notification_fcm_auth_domain'         => $envService->getValue('DEMO') ? 'demo.firebaseapp.com' : '',
            'notification_fcm_project_id'          => $envService->getValue('DEMO') ? 'demo-project' : '',
            'notification_fcm_storage_bucket'      => $envService->getValue('DEMO') ? 'demo.appspot.com' : '',
            'notification_fcm_messaging_sender_id' => $envService->getValue('DEMO') ? '000000000000' : '',
            'notification_fcm_app_id'              => $envService->getValue('DEMO') ? '1:000000000000:web:DEMO' : '',
            'notification_fcm_measurement_id'      => $envService->getValue('DEMO') ? 'G-DEMO' : '',
            'notification_fcm_json_file'           => '',
        ]);

        // Attach a service account json only if it exists locally.
        // This path is intentionally outside the repo to avoid committing credentials.
        $serviceAccountPath = storage_path('app/service-account-file.json');

        if ($envService->getValue('DEMO') && file_exists($serviceAccountPath)) {
            $setting = NotificationSetting::where('key', 'notification_fcm_json_file')->first();

            if ($setting) {
                $setting->addMedia($serviceAccountPath)
                    ->preservingOriginal()
                    ->usingFileName('service-account-file.json')
                    ->toMediaCollection('notification-file');
            }
        }
    }
}

