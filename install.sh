#!/usr/bin/env bash
#
# Support Board PHP - Installation Script
# Sets up MySQL database, runs migrations, seeds data, and configures vLLM
#
# Usage:
#   bash install.sh              # Interactive installation
#   bash install.sh --force      # Non-interactive (all defaults)
#   bash install.sh --skip-seed  # Skip database seeding
#

set -euo pipefail

# ─── Colors & symbols ────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/.env"
ENV_EXAMPLE="${SCRIPT_DIR}/.env.example"

SKIP_SEED=false

# ─── Helper functions ────────────────────────────────────────────────

log_info()  { echo -e "${BLUE}[INFO]${NC}  $*"; }
log_ok()    { echo -e "${GREEN}[✓]${NC} $*"; }
log_warn()  { echo -e "${YELLOW}[!]${NC} $*"; }
log_error() { echo -e "${RED}[✗]${NC} $*" >&2; }
log_step()  { echo -e "\n${BOLD}${CYAN}────────────────────────────────────────────────────${NC}"
              echo -e "${BOLD}${CYAN}  $*${NC}"
              echo -e "${BOLD}${CYAN}────────────────────────────────────────────────────${NC}"; }

ask() {
    local question="$1"
    local default="${2:-y}"
    local force="${3:-false}"
    local prompt
    [[ "$default" == "y" ]] && prompt="[Y/n]" || prompt="[y/N]"

    if [[ "$force" == "true" ]]; then
        [[ "$default" == "y" ]] && echo "y" || echo "n"
        return
    fi

    local answer
    read -rp "${question} ${prompt} " answer
    [[ -z "$answer" ]] && answer="$default"
    echo "$answer"
}

confirm() {
    local response
    response=$(ask "$*" "y")
    [[ "$response" == "y" || "$response" == "Y" ]]
}

parse_dotenv() {
    # Read a value from .env file
    local key="$1"
    local file="${2:-$ENV_FILE}"
    grep "^${key}=" "$file" 2>/dev/null | head -1 | cut -d= -f2- | sed 's/^"//;s/"$//' | sed "s/^'//;s/'$//"
}

update_dotenv() {
    # Update a value in .env file, create if not exists
    local key="$1"
    local value="$2"
    local file="${3:-$ENV_FILE}"

    if grep -q "^${key}=" "$file" 2>/dev/null; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$file"
    else
        echo "${key}=${value}" >> "$file"
    fi
}

# ─── CLI flags ───────────────────────────────────────────────────────

for arg in "$@"; do
    case "$arg" in
        --force)   FORCE_MODE=true  ;;
        --skip-seed) SKIP_SEED=true ;;
        *)         ;;
    esac
done
FORCE_MODE="${FORCE_MODE:-false}"

# ─── Pre-flight checks ───────────────────────────────────────────────

preflight_checks() {
    log_step "Pre-flight Checks"

    if [[ ! -f "${SCRIPT_DIR}/composer.json" ]]; then
        log_error "composer.json not found. Run this from the project root."
        exit 1
    fi

    # PHP
    if command -v php &>/dev/null; then
        PHP_VER=$(php -r "echo PHP_VERSION;")
        log_ok "PHP ${PHP_VER} found"
    else
        log_error "PHP not found. Install PHP 8.1+ first."
        exit 1
    fi

    # PHP version check
    local major minor
    major=$(php -r "echo PHP_MAJOR_VERSION;")
    minor=$(php -r "echo PHP_MINOR_VERSION;")
    if (( major < 8 || (major == 8 && minor < 1) )); then
        log_error "PHP ${major}.${minor} found. PHP 8.1+ required."
        exit 1
    fi

    # MySQL
    if command -v mysql &>/dev/null; then
        log_ok "MySQL client available"
    else
        log_warn "MySQL client not in PATH (you can still create the DB manually)"
    fi

    # Composer
    if command -v composer &>/dev/null; then
        COMPOSER_VER=$(composer --version 2>/dev/null | head -1)
        log_ok "Composer available"
    else
        log_warn "Composer not found (required for dependency installation)"
    fi

    log_ok "Pre-flight checks done"
}

# ─── Step 1: Environment setup ───────────────────────────────────────

setup_environment() {
    log_step "Step 1 — Environment Configuration"

    if [[ -f "$ENV_FILE" ]]; then
        log_warn ".env already exists"
        if confirm "Recreate from .env.example?"; then
            cp "$ENV_EXAMPLE" "$ENV_FILE"
            log_ok "Environment file recreated"
        fi
    else
        if [[ -f "$ENV_EXAMPLE" ]]; then
            cp "$ENV_EXAMPLE" "$ENV_FILE"
            log_ok ".env created from .env.example"
        else
            log_error ".env.example not found"
            exit 1
        fi
    fi

    # ── MySQL database settings ──
    echo ""
    log_info "Enter your MySQL connection details:"
    echo ""

    local db_host db_port db_name db_user db_pass

    db_host=$(ask "MySQL host" "127.0.0.1")
    db_port=$(ask "MySQL port" "3306")
    db_name=$(ask "Database name" "supportboard")
    db_user=$(ask "MySQL username" "root")
    read -rs "db_pass=MySQL password: "
    echo ""

    # Write to .env
    update_dotenv "DB_HOST" "$db_host"
    update_dotenv "DB_PORT" "$db_port"
    update_dotenv "DB_DATABASE" "$db_name"
    update_dotenv "DB_USERNAME" "$db_user"
    update_dotenv "DB_PASSWORD" "$db_pass"

    log_ok "Database: ${db_user}@${db_host}:${db_port}/${db_name}"
}

# ─── Step 2: Create MySQL database ────────────────────────────────────

create_database() {
    log_step "Step 2 — Create MySQL Database"

    local db_host db_port db_user db_pass db_name
    db_host=$(parse_dotenv DB_HOST)
    db_port=$(parse_dotenv DB_PORT)
    db_user=$(parse_dotenv DB_USERNAME)
    db_pass=$(parse_dotenv DB_PASSWORD)
    db_name=$(parse_dotenv DB_DATABASE)

    local mysql_cmd="mysql -h${db_host} -P${db_port} -u${db_user}"
    [[ -n "$db_pass" ]] && mysql_cmd="${mysql_cmd} -p${db_pass}"

    # Test connection
    log_info "Testing MySQL connection..."
    if $mysql_cmd -e "SELECT 1;" &>/dev/null; then
        log_ok "MySQL connection successful"
    else
        log_error "Cannot connect to MySQL."
        echo ""
        log_info "Troubleshooting:"
        echo "  1. Start MySQL:  sudo systemctl start mysql"
        echo "  2. Create DB manually:  mysql -u root -e \"CREATE DATABASE \`${db_name}\`;\""
        echo "  3. Update .env with correct credentials, then re-run this script"
        exit 1
    fi

    # Create database
    log_info "Creating database '${db_name}'..."
    $mysql_cmd -e "CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null && log_ok "Database ready" || log_warn "Database may already exist"
}

# ─── Step 3: Composer dependencies ────────────────────────────────────

install_dependencies() {
    log_step "Step 3 — Install Dependencies"

    cd "$SCRIPT_DIR"

    if [[ -d "vendor" && -f "vendor/autoload.php" ]]; then
        log_warn "vendor/ already exists"
        if confirm "Reinstall dependencies?"; then
            rm -rf vendor composer.lock
        else
            log_info "Skipping composer install"
            return
        fi
    fi

    if command -v composer &>/dev/null; then
        log_info "Running composer install..."
        composer install --no-interaction --optimize-autoloader --no-dev 2>&1 | tail -5
        log_ok "Dependencies installed"
    else
        log_warn "Composer not available. Install manually: composer install"
    fi
}

# ─── Step 4: Generate keys ──────────────────────────────────────────

generate_keys() {
    log_step "Step 4 — Generate Keys & Cache"

    cd "$SCRIPT_DIR"

    if command -v php &>/dev/null; then
        php artisan key:generate --no-interaction 2>/dev/null && log_ok "App key generated" || log_warn "Key generation skipped"

        php artisan jwt:secret --force 2>/dev/null && log_ok "JWT secret generated" || log_warn "JWT secret skipped (package not installed)"

        php artisan config:cache 2>/dev/null && log_ok "Config cached"
        php artisan route:cache 2>/dev/null && log_ok "Routes cached"
    fi
}

# ─── Step 5: Run migrations ─────────────────────────────────────────

run_migrations() {
    log_step "Step 5 — Run Database Migrations"

    cd "$SCRIPT_DIR"

    if command -v php &>/dev/null; then
        log_info "Running migrations..."
        php artisan migrate --force --no-interaction 2>&1 | tail -3
        log_ok "Migrations complete"
    fi
}

# ─── Step 6: Seed database ────────────────────────────────────────────

seed_database() {
    log_step "Step 6 — Seed Database"

    cd "$SCRIPT_DIR"

    if [[ "$SKIP_SEED" == "true" ]]; then
        log_info "Skipping seed (--skip-seed flag)"
        return
    fi

    if command -v php &>/dev/null; then
        if confirm "Seed with demo data? (users, departments, articles, chatbot Q&A)"; then
            log_info "Seeding..."
            php artisan db:seed --class=DatabaseSeeder --force 2>&1 | tail -3
            log_ok "Database seeded"
        else
            log_info "Skipping seed (run: php artisan db:seed)"
        fi
    fi
}

# ─── Step 7: Configure vLLM ───────────────────────────────────────────

configure_vllm() {
    log_step "Step 7 — LLM / vLLM Configuration"

    echo ""
    log_info "Connect the chatbot to an AI model:"
    echo ""
    echo -e "  ${CYAN}vLLM:${NC}      vllm serve meta-llama/Llama-2-7b-chat-hf --port 8000"
    echo -e "  ${CYAN}Ollama:${NC}    ollama run llama2"
    echo -e "  ${CYAN}OpenAI:${NC}    https://api.openai.com/v1"
    echo ""

    local llm_endpoint llm_model llm_api_key llm_temp llm_max_tokens llm_prompt

    llm_endpoint=$(ask "LLM endpoint URL" "http://localhost:8000/v1")
    llm_model=$(ask "Model name" "gpt-3.5-turbo")

    echo -n "  API key (leave blank for local vLLM/Ollama): "
    read -rs llm_api_key
    echo ""

    llm_temp=$(ask "Temperature (0.0–2.0)" "0.7")
    llm_max_tokens=$(ask "Max tokens" "1024")

    llm_prompt=$(ask "System prompt" "You are a helpful customer support assistant. Provide concise, accurate, and friendly responses. If you don't know something, suggest contacting a human agent.")

    update_dotenv "LLM_ENDPOINT" "$llm_endpoint"
    update_dotenv "LLM_MODEL" "$llm_model"
    update_dotenv "LLM_API_KEY" "$llm_api_key"
    update_dotenv "LLM_TEMPERATURE" "$llm_temp"
    update_dotenv "LLM_MAX_TOKENS" "$llm_max_tokens"
    update_dotenv "LLM_SYSTEM_PROMPT" "$llm_prompt"

    log_ok "LLM configured: ${llm_model} → ${llm_endpoint}"
}

# ─── Step 8: Configure web crawling ─────────────────────────────────

configure_crawler() {
    log_step "Step 8 — Web Crawler Configuration (Optional)"

    local max_pages max_depth respect_robots

    max_pages=$(ask "Max pages per crawl" "50")
    max_depth=$(ask "Max crawl depth" "3")
    respect_robots=$(ask "Respect robots.txt" "y")

    update_dotenv "CRAWLER_MAX_PAGES" "$max_pages"
    update_dotenv "CRAWLER_MAX_DEPTH" "$max_depth"
    update_dotenv "CRAWLER_RESPECT_ROBOTS" "$respect_robots"

    log_ok "Crawler configured"
}

# ─── Step 9: Storage setup ──────────────────────────────────────────

setup_storage() {
    log_step "Step 9 — Storage Setup"

    cd "$SCRIPT_DIR"
    mkdir -p storage/framework/{cache,sessions,views} storage/logs
    chmod -R 775 storage
    log_ok "Storage directories ready"
}

# ─── Step 10: Verification ──────────────────────────────────────────

verify_installation() {
    log_step "Step 10 — Verification"

    local errors=0

    [[ -f "$ENV_FILE" ]] && log_ok ".env exists" || { log_error ".env missing"; ((errors++)); }

    local db_host db_port db_user db_pass db_name
    db_host=$(parse_dotenv DB_HOST)
    db_port=$(parse_dotenv DB_PORT)
    db_user=$(parse_dotenv DB_USERNAME)
    db_pass=$(parse_dotenv DB_PASSWORD)
    db_name=$(parse_dotenv DB_DATABASE)

    local mysql_cmd="mysql -h${db_host} -P${db_port} -u${db_user}"
    [[ -n "$db_pass" ]] && mysql_cmd="${mysql_cmd} -p${db_pass}"

    if $mysql_cmd -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${db_name}';" 2>/dev/null | read table_count; then
        log_ok "Database connection OK (${table_count} tables)"
    else
        log_error "Database connection failed"
        ((errors++))
    fi

    if command -v php &>/dev/null && php artisan --version &>/dev/null; then
        log_ok "Laravel $(php artisan --version 2>/dev/null | head -1)"
    else
        log_error "Laravel not working"
        ((errors++))
    fi

    [[ -f "vendor/autoload.php" ]] && log_ok "Dependencies installed" || { log_error "Missing vendor/autoload.php"; ((errors++)); }

    [[ $errors -gt 0 ]] && { log_error "${errors} error(s) found"; return 1; }
    log_ok "All checks passed!"
}

# ─── Final summary ──────────────────────────────────────────────────

show_summary() {
    log_step "✅ Installation Complete"

    echo ""
    log_info "Quick start:"
    echo "  Start server:   php artisan serve"
    echo "  Run migrations: php artisan migrate"
    echo "  Seed database:  php artisan db:seed"
    echo "  Crawl websites: php artisan crawl:sources"
    echo ""
    log_info "Demo accounts (after seeding):"
    echo "  Admin:  admin@supportboard.local / admin123"
    echo "  Agent:  agent@supportboard.local / agent123"
    echo "  User:   user@example.com / user123"
    echo ""
    log_info "Key APIs:"
    echo "  POST /api/auth/login"
    echo "  POST /api/chatbots/chat"
    echo "  POST /api/crawl-sources/crawl-url"
    echo "  GET  /api/crawl-sources"
    echo ""

    local llm_ep llm_model
    llm_ep=$(parse_dotenv LLM_ENDPOINT)
    llm_model=$(parse_dotenv LLM_MODEL)
    [[ -n "$llm_ep" ]] && log_info "LLM: ${llm_model} at ${llm_ep}"
}

# ─── Main ─────────────────────────────────────────────────────────────

main() {
    log_step "Support Board PHP — Installation Wizard"
    echo ""

    if [[ "$FORCE_MODE" == "false" ]]; then
        if ! confirm "Begin installation?"; then
            log_info "Cancelled."
            exit 0
        fi
    fi

    preflight_checks
    setup_environment
    create_database
    install_dependencies
    generate_keys
    run_migrations
    seed_database
    configure_vllm
    configure_crawler
    setup_storage
    verify_installation
    show_summary

    echo ""
    log_ok "You're all set! 🚀"
}

main "$@"
