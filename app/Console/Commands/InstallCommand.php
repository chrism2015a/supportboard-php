<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    protected $signature = 'supportboard:install
                            {--force : Force the operation to run without confirmation}
                            {--skip-seed : Skip database seeding}
                            {--skip-composer : Skip composer install}
                            {--skip-cache : Skip config/route caching}
                            {--db-host=* : Database host (can specify multiple)}
                            {--db-port=3306 : Database port}
                            {--db-name=supportboard : Database name}
                            {--db-user=root : Database username}
                            {--db-password= : Database password}
                            {--llm-endpoint=http://localhost:8000/v1 : LLM endpoint}
                            {--llm-model=gpt-3.5-turbo : LLM model name}
                            {--llm-api-key= : LLM API key}
                            {--llm-temperature=0.7 : LLM temperature}
                            {--llm-max-tokens=1024 : LLM max tokens}
                            {--llm-system-prompt= : LLM system prompt}';

    protected $description = 'Install and configure Support Board PHP (database, migrations, seeding, vLLM)';

    private array $results = [];
    private int $errors = 0;

    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Begin Support Board installation? This will configure your database and install dependencies.')) {
                return static::FAILURE;
            }
        }

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║   Support Board PHP - Installation Wizard            ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->info('');

        // Step 1: Environment
        $this->step('1', 'Environment Setup', function () {
            $envPath = base_path('.env');
            $envExamplePath = base_path('.env.example');

            if (File::exists($envPath)) {
                $this->warn('.env file already exists.');
                if ($this->confirm('Recreate from .env.example?')) {
                    copy($envExamplePath, $envPath);
                    $this->info('Environment file recreated.');
                } else {
                    $this->info('Keeping existing .env file.');
                }
            } else {
                if (File::exists($envExamplePath)) {
                    copy($envExamplePath, $envPath);
                    $this->info('.env file created.');
                } else {
                    $this->error('.env.example not found.');
                    return false;
                }
            }

            // Configure DB
            $dbHost = $this->option('db-host') ?: $this->ask('MySQL host', '127.0.0.1');
            $dbPort = $this->option('db-port') ?: $this->ask('MySQL port', '3306');
            $dbName = $this->option('db-name') ?: $this->ask('Database name', 'supportboard');
            $dbUser = $this->option('db-user') ?: $this->ask('MySQL username', 'root');
            $dbPass = $this->option('db-password') ?: $this->secret('MySQL password');

            $this->updateEnv('DB_HOST', $dbHost);
            $this->updateEnv('DB_PORT', $dbPort);
            $this->updateEnv('DB_DATABASE', $dbName);
            $this->updateEnv('DB_USERNAME', $dbUser);
            $this->updateEnv('DB_PASSWORD', $dbPass);

            $this->info("Database configured: {$dbUser}@{$dbHost}:{$dbPort}/{$dbName}");
            return true;
        });

        // Step 2: Create database
        $this->step('2', 'Create MySQL Database', function () {
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $database = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');

            $this->info('Testing MySQL connection...');
            try {
                DB::connection()->getPdo();
                $this->info('MySQL connection successful.');
            } catch (\Exception $e) {
                $this->error('Cannot connect to MySQL. Verify credentials and try again.');
                $this->info('You can create the DB manually: mysql -u root -e "CREATE DATABASE ' . $database . ';"');
                return false;
            }

            try {
                DB::connection()->getPdo()->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->info("Database '{$database}' is ready.");
            } catch (\Exception $e) {
                $this->warn('Database may already exist.');
            }
            return true;
        });

        // Step 3: Composer
        $this->step('3', 'Install Dependencies', function () {
            if ($this->option('skip-composer')) {
                $this->info('Skipping composer install.');
                return true;
            }

            if (File::exists(base_path('vendor/autoload.php'))) {
                $this->warn('vendor/ directory already exists.');
                if ($this->confirm('Reinstall dependencies?')) {
                    File::deleteDirectory(base_path('vendor'));
                    File::delete(base_path('composer.lock'));
                } else {
                    $this->info('Skipping composer install.');
                    return true;
                }
            }

            $this->info('Running composer install...');
            $output = [];
            $exitCode = 0;
            exec('composer install --no-interaction --optimize-autoloader 2>&1', $output, $exitCode);
            if ($exitCode === 0) {
                $this->info('Dependencies installed.');
                return true;
            }
            $this->error('Composer failed. Check output above.');
            return false;
        });

        // Step 4: Keys & cache
        $this->step('4', 'Generate Keys & Cache', function () {
            Artisan::call('key:generate', ['--no-interaction' => true, '--force' => true]);
            $this->info('Application key generated.');

            Artisan::call('jwt:secret', ['--force' => true]);
            $this->info('JWT secret generated.');

            if (!$this->option('skip-cache')) {
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                $this->info('Configuration and routes cached.');
            }
            return true;
        });

        // Step 5: Migrations
        $this->step('5', 'Run Migrations', function () {
            $this->info('Running migrations...');
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            $this->info('Migrations complete.');
            return true;
        });

        // Step 6: Seed
        $this->step('6', 'Seed Database', function () {
            if ($this->option('skip-seed')) {
                $this->info('Skipping seed (--skip-seed).');
                return true;
            }

            if ($this->confirm('Seed with demo data? (users, departments, articles, chatbot Q&A)')) {
                $this->info('Seeding...');
                Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
                $this->info('Database seeded.');
            } else {
                $this->info('Skipping seed (run: php artisan db:seed).');
            }
            return true;
        });

        // Step 7: LLM/vLLM
        $this->step('7', 'LLM / vLLM Configuration', function () {
            $this->info('');
            $this->info('Connect the chatbot to an AI model:');
            $this->info('  vLLM:    vllm serve meta-llama/Llama-2-7b-chat-hf --port 8000');
            $this->info('  Ollama:  ollama run llama2');
            $this->info('  OpenAI:  https://api.openai.com/v1');
            $this->info('');

            $llmEndpoint = $this->option('llm-endpoint') ?: $this->ask('LLM endpoint URL', 'http://localhost:8000/v1');
            $llmModel = $this->option('llm-model') ?: $this->ask('Model name', 'gpt-3.5-turbo');
            $llmApiKey = $this->option('llm-api-key') ?: $this->secret('API key (leave blank for local vLLM/Ollama)');
            $llmTemp = $this->option('llm-temperature') ?: $this->ask('Temperature (0.0-2.0)', '0.7');
            $llmMaxTokens = $this->option('llm-max-tokens') ?: $this->ask('Max tokens', '1024');
            $llmPrompt = $this->option('llm-system-prompt') ?: $this->ask(
                'System prompt',
                'You are a helpful customer support assistant. Provide concise, accurate, and friendly responses.'
            );

            $this->updateEnv('LLM_ENDPOINT', $llmEndpoint);
            $this->updateEnv('LLM_MODEL', $llmModel);
            $this->updateEnv('LLM_API_KEY', $llmApiKey);
            $this->updateEnv('LLM_TEMPERATURE', $llmTemp);
            $this->updateEnv('LLM_MAX_TOKENS', $llmMaxTokens);
            $this->updateEnv('LLM_SYSTEM_PROMPT', $llmPrompt);

            $this->info("LLM configured: {$llmModel} at {$llmEndpoint}");
            return true;
        });

        // Step 8: Crawler
        $this->step('8', 'Web Crawler Configuration (Optional)', function () {
            $maxPages = $this->ask('Max pages per crawl', '50');
            $maxDepth = $this->ask('Max crawl depth', '3');
            $respectRobots = $this->ask('Respect robots.txt', 'y');

            $this->updateEnv('CRAWLER_MAX_PAGES', $maxPages);
            $this->updateEnv('CRAWLER_MAX_DEPTH', $maxDepth);
            $this->updateEnv('CRAWLER_RESPECT_ROBOTS', $respectRobots);

            $this->info('Crawler configured.');
            return true;
        });

        // Step 9: Storage
        $this->step('9', 'Storage Setup', function () {
            File::ensureDirectoryExists(storage_path('framework/cache'));
            File::ensureDirectoryExists(storage_path('framework/sessions'));
            File::ensureDirectoryExists(storage_path('framework/views'));
            File::ensureDirectoryExists(storage_path('logs'));
            chmod(storage_path('framework'), 0775);
            chmod(storage_path('logs'), 0775);
            $this->info('Storage directories ready.');
            return true;
        });

        // Step 10: Verify
        $this->step('10', 'Verification', function () {
            $errors = 0;

            File::exists(base_path('.env'))
                ? $this->info('✓ .env exists')
                : $this->error('✗ .env missing') && $errors++;

            try {
                $tableCount = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [config('database.connections.mysql.database')]);
                $this->info("✓ Database connection OK ({$tableCount[0]->count} tables)");
            } catch (\Exception $e) {
                $this->error('✗ Database connection failed');
                $errors++;
            }

            $version = Artisan::version();
            $this->info("✓ Laravel {$version}");

            File::exists(base_path('vendor/autoload.php'))
                ? $this->info('✓ Dependencies installed')
                : $this->error('✗ Missing vendor/autoload.php') && $errors++;

            if ($errors > 0) {
                $this->error("{$errors} error(s) found. See above.");
                return false;
            }

            $this->info('All checks passed!');
            return true;
        });

        // Summary
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║   ✅ Installation Complete!                          ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->info('');
        $this->info('Quick start:');
        $this->info('  Start server:   php artisan serve');
        $this->info('  Run migrations: php artisan migrate');
        $this->info('  Seed database:  php artisan db:seed');
        $this->info('  Crawl websites: php artisan crawl:sources');
        $this->info('');
        $this->info('Demo accounts (after seeding):');
        $this->info('  Admin:  admin@supportboard.local / admin123');
        $this->info('  Agent:  agent@supportboard.local / agent123');
        $this->info('  User:   user@example.com / user123');
        $this->info('');
        $this->info('Key APIs:');
        $this->info('  POST /api/auth/login');
        $this->info('  POST /api/chatbots/chat');
        $this->info('  POST /api/crawl-sources/crawl-url');
        $this->info('');

        $llmEp = $this->option('llm-endpoint') ?: config('services.llm.endpoint');
        $llmMod = $this->option('llm-model') ?: config('services.llm.model');
        if ($llmEp) {
            $this->info("LLM: {$llmMod} at {$llmEp}");
        }

        $this->info('');
        $this->info('You\'re all set! 🚀');
        return static::SUCCESS;
    }

    private function step(string $number, string $title, callable $callback): void
    {
        $this->info('');
        $this->info("────────────────────────────────────────────────────");
        $this->info("  Step {$number}: {$title}");
        $this->info("────────────────────────────────────────────────────");

        try {
            $result = $callback();
            $this->results[] = ['step' => $number, 'title' => $title, 'success' => $result ?? true];
        } catch (\Exception $e) {
            $this->error("Step {$number} failed: {$e->getMessage()}");
            $this->results[] = ['step' => $number, 'title' => $title, 'success' => false];
            $this->errors++;
        }
    }

    private function updateEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        if (file_exists($path)) {
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
}
