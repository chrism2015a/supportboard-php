<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class InstallerController extends Controller
{
    public function index()
    {
        return view('installer.welcome');
    }

    /**
     * Check system requirements.
     */
    public function check()
    {
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.1.0', '>=');

        // Check MySQL
        $mysqlOk = false;
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;port=3306', 'root', '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $mysqlOk = true;
        } catch (\Exception $e) {
            $mysqlOk = 'unknown';
        }

        // Check Composer
        $composerVersion = null;
        if (function_exists('exec')) {
            exec('composer --version 2>&1', $output, $return);
            if ($return === 0 && !empty($output)) {
                $composerVersion = trim($output[0]);
            }
        }

        // Check Laravel
        $laravelVersion = null;
        try {
            $laravelVersion = \Illuminate\Foundation\Application::VERSION;
        } catch (\Exception $e) {
            $laravelVersion = null;
        }

        return response()->json([
            'php' => $phpOk ? $phpVersion : false,
            'mysql' => $mysqlOk,
            'composer' => $composerVersion,
            'laravel' => $laravelVersion,
        ]);
    }

    /**
     * Test database connection.
     */
    public function testDb(Request $request)
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|integer',
            'db_name' => 'required|string',
            'db_user' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $host = $request->db_host;
        $port = $request->db_port;
        $database = $request->db_name;
        $user = $request->db_user;
        $password = $request->db_password;

        try {
            // Test connection
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            // Try to create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            return response()->json([
                'success' => true,
                'message' => "Connected successfully. Database '{$database}' is ready.",
                'created' => true,
            ]);
        } catch (\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Run the full installation.
     */
    public function run(Request $request)
    {
        $request->validate([
            'llm_endpoint' => 'nullable|url',
            'llm_model' => 'nullable|string',
            'llm_api_key' => 'nullable|string',
            'llm_temperature' => 'nullable|numeric|min:0|max:2',
            'llm_max_tokens' => 'nullable|integer|min:1',
            'llm_system_prompt' => 'nullable|string',
            'seed' => 'nullable|boolean',
            'whatsapp_api_key' => 'nullable|string',
            'whatsapp_phone_number_id' => 'nullable|string',
            'whatsapp_message_template_id' => 'nullable|string',
            'whatsapp_auto_reply' => 'nullable|string',
            'whatsapp_default_department_id' => 'nullable|integer',
            'whatsapp_webhook_verify_token' => 'nullable|string',
            'whatsapp_verify_token' => 'nullable|string',
        ]);

        $results = [
            'success' => false,
            'keys' => false,
            'migrations' => false,
            'seed' => true,
            'llm' => false,
            'crawler' => false,
            'whatsapp' => false,
            'message' => '',
        ];

        // Step 1: Generate keys
        try {
            Artisan::call('key:generate', ['--no-interaction' => true, '--force' => true]);
            Artisan::call('jwt:secret', ['--force' => true]);
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            $results['keys'] = true;
        } catch (\Exception $e) {
            $results['message'] .= 'Key generation failed: ' . $e->getMessage() . "\n";
        }

        // Step 2: Migrations
        try {
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            $results['migrations'] = true;
        } catch (\Exception $e) {
            $results['message'] .= 'Migrations failed: ' . $e->getMessage() . "\n";
        }

        // Step 3: Seed
        if ($request->seed !== false) {
            try {
                Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
            } catch (\Exception $e) {
                $results['seed'] = false;
                $results['message'] .= 'Seeding failed: ' . $e->getMessage() . "\n";
            }
        }

        // Step 4: Configure LLM
        if ($request->llm_endpoint) {
            $this->updateEnv('LLM_ENDPOINT', $request->llm_endpoint);
            $this->updateEnv('LLM_MODEL', $request->llm_model ?? 'gpt-3.5-turbo');
            $this->updateEnv('LLM_API_KEY', $request->llm_api_key ?? '');
            $this->updateEnv('LLM_TEMPERATURE', $request->llm_temperature ?? 0.7);
            $this->updateEnv('LLM_MAX_TOKENS', $request->llm_max_tokens ?? 1024);
            $this->updateEnv('LLM_SYSTEM_PROMPT', $request->llm_system_prompt ?? 'You are a helpful customer support assistant.');
            $results['llm'] = true;
        }

        // Step 5: Configure crawler
        $this->updateEnv('CRAWLER_MAX_PAGES', $request->crawler_max_pages ?? 50);
        $this->updateEnv('CRAWLER_MAX_DEPTH', $request->crawler_max_depth ?? 3);
        $this->updateEnv('CRAWLER_RESPECT_ROBOTS', $request->crawler_respect_robots ?? true);
        $results['crawler'] = true;

        // Step 6: Configure WhatsApp
        if ($request->whatsapp_api_key) {
            $this->updateEnv('WHATSAPP_API_KEY', $request->whatsapp_api_key);
            $this->updateEnv('WHATSAPP_PHONE_NUMBER_ID', $request->whatsapp_phone_number_id ?? '');
            $this->updateEnv('WHATSAPP_MESSAGE_TEMPLATE_ID', $request->whatsapp_message_template_id ?? '');
            $this->updateEnv('WHATSAPP_AUTO_REPLY', $request->whatsapp_auto_reply ?? '');
            $this->updateEnv('WHATSAPP_WEBHOOK_VERIFY_TOKEN', $request->whatsapp_webhook_verify_token ?? 'supportboard_verify_token');
            $this->updateEnv('WHATSAPP_VERIFY_TOKEN', $request->whatsapp_verify_token ?? 'supportboard_verify_token');
            $this->updateEnv('WHATSAPP_DEFAULT_DEPARTMENT_ID', $request->whatsapp_default_department_id ?? 1);
            $results['whatsapp'] = true;
        }

        // Check if all critical steps passed
        $results['success'] = $results['migrations'] && $results['keys'];
        $results['message'] = $results['success'] ? 'Installation complete!' : $results['message'];

        return response()->json($results);
    }

    protected function updateEnv(string $key, $value): void
    {
        $path = base_path('.env');
        if (!file_exists($path)) return;

        $content = file_get_contents($path);
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }
}
