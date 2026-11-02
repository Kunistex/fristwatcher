<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Für Hosting ohne SSH: Führt nach dem FTP-Upload die Artisan-Befehle aus
 * und dient als Web-Cron für den Scheduler.
 *
 * Abgesichert über DEPLOY_HOOK_TOKEN in der .env (mind. 32 Zeichen).
 * Ohne Token antwortet der Endpunkt mit 404.
 */
class DeployHookController extends Controller
{
    public function deploy(Request $request): Response
    {
        $this->authorizeToken($request->header('X-Deploy-Token'));

        $this->ensureStorage();
        $log = [];
        $run = function (string $command, array $args = []) use (&$log) {
            $code = Artisan::call($command, $args);
            $log[] = "$ {$command} → {$code}\n".trim(Artisan::output());
            if ($code !== 0) {
                throw new \RuntimeException("{$command} fehlgeschlagen");
            }
        };

        try {
            // Einmalige Einrichtung: fehlende Schlüssel in die .env schreiben.
            if (blank(config('app.key'))) {
                $run('key:generate', ['--force' => true]);
            }
            if (blank(config('webpush.vapid.public_key'))) {
                try {
                    $run('webpush:vapid', ['--force' => true]);
                } catch (\Throwable $e) {
                    $log[] = 'Hinweis: VAPID-Schlüssel nicht erzeugt ('.$e->getMessage().'). Push bleibt aus, bis VAPID_* in der .env steht.';
                }
            }

            $run('migrate', ['--force' => true]);
            $run('db:seed', ['--class' => 'Database\\Seeders\\ReferenceDataSeeder', '--force' => true]);
            $run('optimize:clear');
            $run('optimize');

            if (! file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }
        } catch (\Throwable $e) {
            $log[] = 'FEHLER: '.$e->getMessage();

            return response(implode("\n\n", $log), 500)->header('Content-Type', 'text/plain');
        }

        $log[] = 'Deployment fertig.';

        return response(implode("\n\n", $log))->header('Content-Type', 'text/plain');
    }

    /** Web-Cron: jede Minute aufrufen, falls das Hosting nur URL-Cronjobs kennt. */
    public function schedule(Request $request): Response
    {
        $this->authorizeToken($request->query('token'));
        $this->ensureStorage();

        Artisan::call('schedule:run');

        return response(trim(Artisan::output()) ?: 'ok')->header('Content-Type', 'text/plain');
    }

    private function authorizeToken(?string $given): void
    {
        $expected = (string) config('fristwatcher.deploy_hook_token');

        abort_if(strlen($expected) < 32, 404);
        abort_unless(is_string($given) && hash_equals($expected, $given), 403);
    }

    private function ensureStorage(): void
    {
        foreach ([
            'app/private', 'app/public', 'framework/cache/data', 'framework/sessions',
            'framework/views', 'logs', 'fonts',
        ] as $dir) {
            $path = storage_path($dir);
            if (! is_dir($path)) {
                @mkdir($path, 0775, true);
            }
        }
    }
}
