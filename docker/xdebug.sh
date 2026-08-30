#!/usr/bin/env bash
# =============================================================================
# Laravel Gravity - Xdebug Management Tool
# =============================================================================
# Facilitates easy toggling and management of Xdebug 3 modes:
# - Step Debugging (mode: debug)
# - Performance Profiling (mode: profile)
# - Function Tracing (mode: trace)
# - Code Coverage Analysis (mode: coverage)
# - Development Helpers (mode: develop)
# - Disable (mode: off)
# =============================================================================

set -e

SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
    DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
    SOURCE="$(readlink "$SOURCE")"
    [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE"
done
SCRIPT_DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
ROOT_DIR="$(cd -P "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ROOT_DIR}/.env"
XDEBUG_DIR="${ROOT_DIR}/storage/logs/xdebug"
COVERAGE_DIR="${ROOT_DIR}/storage/logs/coverage"

# Text Styling
BOLD='\033[1m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Helper to update key-value in .env file
update_env_var() {
    local key="$1"
    local value="$2"

    if [ ! -f "$ENV_FILE" ]; then
        echo -e "${RED}Error: .env file not found at ${ENV_FILE}${NC}"
        exit 1
    fi

    if grep -q "^${key}=" "$ENV_FILE"; then
        # Replace existing
        sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
    else
        # Append
        echo "${key}=${value}" >> "$ENV_FILE"
    fi
}

get_env_var() {
    local key="$1"
    local default="$2"
    if [ -f "$ENV_FILE" ]; then
        local val
        val=$(grep "^${key}=" "$ENV_FILE" | head -n 1 | cut -d '=' -f2- | tr -d '"' | tr -d "'")
        if [ -n "$val" ]; then
            echo "$val"
            return
        fi
    fi
    echo "$default"
}

ensure_directories() {
    mkdir -p "$XDEBUG_DIR"
    if [ ! -f "$XDEBUG_DIR/.gitignore" ]; then
        echo -e "*\n!.gitignore" > "$XDEBUG_DIR/.gitignore"
    fi
    mkdir -p "$COVERAGE_DIR"
    if [ ! -f "$COVERAGE_DIR/.gitignore" ]; then
        echo -e "*\n!.gitignore" > "$COVERAGE_DIR/.gitignore"
    fi
}

cmd_status() {
    ensure_directories
    echo -e "${BOLD}${BLUE}=====================================================${NC}"
    echo -e "${BOLD}${BLUE}        Laravel Gravity - Xdebug Status              ${NC}"
    echo -e "${BOLD}${BLUE}=====================================================${NC}"
    echo -e "${BOLD}1. Environment Configuration (.env):${NC}"
    echo -e "  ${CYAN}XDEBUG_MODE${NC}:               $(get_env_var 'XDEBUG_MODE' 'off')"
    echo -e "  ${CYAN}XDEBUG_START_WITH_REQUEST${NC}: $(get_env_var 'XDEBUG_START_WITH_REQUEST' 'trigger')"
    echo -e "  ${CYAN}XDEBUG_CONFIG${NC}:             $(get_env_var 'XDEBUG_CONFIG' 'client_host=host.docker.internal client_port=9003')"
    echo -e "  ${CYAN}XDEBUG_IDEKEY${NC}:             $(get_env_var 'XDEBUG_IDEKEY' 'PHPSTORM')"
    echo ""

    if docker compose ps --services --filter "status=running" 2>/dev/null | grep -q "^app$"; then
        echo -e "${BOLD}2. Active Container Runtime (laravel-gravity-app):${NC}"
        local active_modes
        active_modes=$(docker compose exec -T app php -r "echo implode(', ', xdebug_info('mode'));" 2>/dev/null || echo "none (off)")
        local is_loaded
        is_loaded=$(docker compose exec -T app php -r "echo extension_loaded('xdebug') ? 'YES' : 'NO';" 2>/dev/null || echo "NO")
        local client_host
        client_host=$(docker compose exec -T app php -r "echo ini_get('xdebug.client_host');" 2>/dev/null || echo "host.docker.internal")
        local client_port
        client_port=$(docker compose exec -T app php -r "echo ini_get('xdebug.client_port');" 2>/dev/null || echo "9003")
        local start_req
        start_req=$(docker compose exec -T app php -r "echo ini_get('xdebug.start_with_request');" 2>/dev/null || echo "trigger")

        if [ "$is_loaded" = "YES" ]; then
            echo -e "  Extension Loaded:             ${GREEN}YES (Xdebug 3)${NC}"
        else
            echo -e "  Extension Loaded:             ${RED}NO${NC}"
        fi

        if [ -n "$active_modes" ] && [ "$active_modes" != "none (off)" ]; then
            echo -e "  Active Mode(s):               ${GREEN}${BOLD}${active_modes}${NC}"
        else
            echo -e "  Active Mode(s):               ${YELLOW}off (No overhead)${NC}"
        fi
        echo -e "  Client Host (IDE target):     ${CYAN}${client_host}${NC}"
        echo -e "  Client Port:                  ${CYAN}${client_port}${NC}"
        echo -e "  Start With Request:           ${start_req}"
        echo -e "  Output Directory:             /var/www/html/storage/logs/xdebug"
    else
        echo -e "${YELLOW}Container 'app' is not running. Run 'docker compose up -d' to start.${NC}"
    fi

    echo ""
    echo -e "${BOLD}3. Generated Artifacts:${NC}"
    local profiles
    profiles=$(find "$XDEBUG_DIR" -maxdepth 1 -name "cachegrind.out.*" 2>/dev/null | wc -l)
    local traces
    traces=$(find "$XDEBUG_DIR" -maxdepth 1 -name "trace.*.xt" 2>/dev/null | wc -l)
    local logs
    logs=$(test -f "$XDEBUG_DIR/xdebug.log" && ls -lh "$XDEBUG_DIR/xdebug.log" | awk '{print $5}' || echo "None")
    local coverage
    coverage=$(test -d "$COVERAGE_DIR" && find "$COVERAGE_DIR" -maxdepth 1 -name "*.html" 2>/dev/null | wc -l || echo "0")

    echo -e "  Profiler Snapshots (cachegrind): ${profiles} file(s) in storage/logs/xdebug/"
    echo -e "  Trace Logs:                     ${traces} file(s) in storage/logs/xdebug/"
    echo -e "  HTML Coverage Reports:          ${coverage} file(s) in storage/logs/coverage/"
    echo -e "  Xdebug Diagnostic Log:          ${logs}"
    echo -e "${BOLD}${BLUE}=====================================================${NC}"
}

cmd_on() {
    local mode="${1:-debug}"
    ensure_directories
    echo -e "${GREEN}Enabling Xdebug (mode: ${mode})...${NC}"
    update_env_var "XDEBUG_MODE" "$mode"
    echo -e "${BLUE}Applying changes to app and queue containers...${NC}"
    docker compose up -d app queue web
    echo -e "${GREEN}✓ Xdebug is now ACTIVE in mode '${mode}'.${NC}"
    if [[ "$mode" == *"debug"* ]]; then
        echo -e "${CYAN}Tip: IDE can now listen on port 9003. Trigger via browser extension or ?XDEBUG_SESSION=PHPSTORM${NC}"
    fi
}

cmd_off() {
    echo -e "${YELLOW}Disabling Xdebug (mode: off)...${NC}"
    update_env_var "XDEBUG_MODE" "off"
    echo -e "${BLUE}Applying changes to app and queue containers...${NC}"
    docker compose up -d app queue web
    echo -e "${GREEN}✓ Xdebug disabled (maximum performance, zero overhead).${NC}"
}

cmd_mode() {
    local mode="$1"
    if [ -z "$mode" ]; then
        echo -e "${RED}Error: Please specify a mode.${NC}"
        echo -e "Available modes: off, debug, develop, trace, profile, coverage (or combinations like debug,profile)"
        exit 1
    fi
    ensure_directories
    echo -e "${GREEN}Switching Xdebug mode to '${mode}'...${NC}"
    update_env_var "XDEBUG_MODE" "$mode"
    echo -e "${BLUE}Applying changes to app and queue containers...${NC}"
    docker compose up -d app queue web
    echo -e "${GREEN}✓ Xdebug mode successfully set to '${mode}'.${NC}"
}

cmd_trigger() {
    local trigger="$1"
    if [ "$trigger" != "yes" ] && [ "$trigger" != "no" ] && [ "$trigger" != "trigger" ]; then
        echo -e "${RED}Error: Trigger value must be one of: 'yes', 'no', 'trigger'.${NC}"
        exit 1
    fi
    echo -e "${GREEN}Setting XDEBUG_START_WITH_REQUEST=${trigger}...${NC}"
    update_env_var "XDEBUG_START_WITH_REQUEST" "$trigger"
    docker compose up -d app queue web
    echo -e "${GREEN}✓ XDEBUG_START_WITH_REQUEST set to '${trigger}'.${NC}"
}

cmd_run_debug() {
    if [ $# -eq 0 ]; then
        echo -e "${RED}Error: Specify a command to run with step debugging.${NC}"
        echo -e "Example: ./xdebug run-debug php artisan migrate"
        exit 1
    fi
    echo -e "${GREEN}Running command with Step Debugging enabled (port 9003)...${NC}"
    docker compose exec -e XDEBUG_MODE=debug -e XDEBUG_CONFIG="start_with_request=yes client_host=host.docker.internal client_port=9003" app "$@"
}

cmd_run_trace() {
    if [ $# -eq 0 ]; then
        echo -e "${RED}Error: Specify a command to trace.${NC}"
        echo -e "Example: ./xdebug run-trace php artisan test"
        exit 1
    fi
    ensure_directories
    echo -e "${GREEN}Executing command with Function Tracing enabled...${NC}"
    docker compose exec -e XDEBUG_MODE=trace -e XDEBUG_CONFIG="start_with_request=yes output_dir=/var/www/html/storage/logs/xdebug" app "$@"
    echo ""
    echo -e "${GREEN}✓ Trace generated in storage/logs/xdebug/:${NC}"
    ls -lht "$XDEBUG_DIR"/trace.*.xt 2>/dev/null | head -n 5 || true
}

cmd_run_profile() {
    if [ $# -eq 0 ]; then
        echo -e "${RED}Error: Specify a command to profile.${NC}"
        echo -e "Example: ./xdebug run-profile php artisan route:list"
        exit 1
    fi
    ensure_directories
    echo -e "${GREEN}Executing command with Performance Profiling enabled...${NC}"
    docker compose exec -e XDEBUG_MODE=profile -e XDEBUG_CONFIG="start_with_request=yes output_dir=/var/www/html/storage/logs/xdebug" app "$@"
    echo ""
    echo -e "${GREEN}✓ Cachegrind profile snapshot generated in storage/logs/xdebug/:${NC}"
    ls -lht "$XDEBUG_DIR"/cachegrind.out.* 2>/dev/null | head -n 5 || true
    echo -e "${CYAN}Tip: Open in PhpStorm via Tools > Analyze Xdebug Profiler Snapshot, or in VSCode / KCachegrind / Webgrind.${NC}"
}

cmd_test_coverage() {
    ensure_directories
    local generate_html=false
    local args=()

    for arg in "$@"; do
        if [ "$arg" == "--html" ]; then
            generate_html=true
        else
            args+=("$arg")
        fi
    done

    echo -e "${GREEN}Running test suite with Code Coverage Analysis...${NC}"
    if [ "$generate_html" = true ]; then
        docker compose exec -e XDEBUG_MODE=coverage app vendor/bin/phpunit --coverage-html storage/logs/coverage "${args[@]}"
        echo ""
        echo -e "${GREEN}✓ HTML Code Coverage Report generated at:${NC}"
        echo -e "  ${CYAN}${COVERAGE_DIR}/index.html${NC}"
    else
        if [ ${#args[@]} -eq 0 ]; then
            docker compose exec -e XDEBUG_MODE=coverage app vendor/bin/phpunit --coverage-text
        else
            docker compose exec -e XDEBUG_MODE=coverage app vendor/bin/phpunit "${args[@]}"
        fi
    fi
}

cmd_clean() {
    ensure_directories
    echo -e "${YELLOW}Cleaning up Xdebug traces, profiles, logs, and coverage reports...${NC}"
    find "$XDEBUG_DIR" -type f ! -name ".gitignore" -delete 2>/dev/null || true
    find "$COVERAGE_DIR" -type f ! -name ".gitignore" -delete 2>/dev/null || true
    echo -e "${GREEN}✓ Cleaned storage/logs/xdebug/ and storage/logs/coverage/.${NC}"
}

cmd_logs() {
    ensure_directories
    local log_file="$XDEBUG_DIR/xdebug.log"
    if [ ! -f "$log_file" ] || [ ! -s "$log_file" ]; then
        echo -e "${YELLOW}Xdebug log file is currently empty (${log_file}).${NC}"
        exit 0
    fi
    if [ "$1" == "-f" ]; then
        tail -f "$log_file"
    else
        tail -n 50 "$log_file"
    fi
}

cmd_help() {
    echo -e "${BOLD}${BLUE}=====================================================${NC}"
    echo -e "${BOLD}${BLUE}        Laravel Gravity - Xdebug CLI Tool            ${NC}"
    echo -e "${BOLD}${BLUE}=====================================================${NC}"
    echo ""
    echo -e "${BOLD}USAGE:${NC}"
    echo -e "  ./xdebug <command> [arguments...]"
    echo ""
    echo -e "${BOLD}GLOBAL TOGGLE & MODE SWITCHING (Persisted in .env):${NC}"
    echo -e "  ${GREEN}status${NC}                     Display current Xdebug status, configuration & generated files"
    echo -e "  ${GREEN}off${NC}                        Turn OFF Xdebug completely (0% performance overhead)"
    echo -e "  ${GREEN}on [mode]${NC}                  Turn ON Xdebug (default mode: debug)"
    echo -e "  ${GREEN}mode <mode>${NC}                Switch mode: debug | trace | profile | coverage | develop"
    echo -e "  ${GREEN}trigger <yes|trigger|no>${NC}   Configure start_with_request behavior"
    echo ""
    echo -e "${BOLD}ON-DEMAND CLI EXECUTION (Instant, no container recreation needed):${NC}"
    echo -e "  ${GREEN}run-debug <command>${NC}        Run any CLI/Artisan command with Step Debugging"
    echo -e "  ${GREEN}run-trace <command>${NC}        Run command with Function Tracing (saves to storage/logs/xdebug/)"
    echo -e "  ${GREEN}run-profile <command>${NC}      Run command with Performance Profiling (Cachegrind dump)"
    echo -e "  ${GREEN}test-coverage [--html]${NC}     Run PHPUnit suite with Code Coverage (text summary or HTML report)"
    echo ""
    echo -e "${BOLD}UTILITIES:${NC}"
    echo -e "  ${GREEN}clean${NC}                      Delete all generated traces, profiles, logs, and coverage reports"
    echo -e "  ${GREEN}logs [-f]${NC}                  View or follow Xdebug diagnostic log file"
    echo -e "  ${GREEN}help${NC}                       Show this help guide"
    echo ""
    echo -e "${BOLD}COMMON WORKFLOWS:${NC}"
    echo -e "  ${MAGENTA}• Step Debugging Web Requests:${NC}"
    echo -e "      1. Run: ${CYAN}./xdebug on debug${NC}"
    echo -e "      2. Start debugger listening in VS Code or PhpStorm (Port 9003)"
    echo -e "      3. Set a breakpoint in your controller/code"
    echo -e "      4. Open URL in browser with Xdebug extension or ${CYAN}?XDEBUG_SESSION=PHPSTORM${NC}"
    echo ""
    echo -e "  ${MAGENTA}• Profiling Web or API Requests:${NC}"
    echo -e "      1. Run: ${CYAN}./xdebug on profile${NC}"
    echo -e "      2. Visit URL with ${CYAN}?XDEBUG_TRIGGER=1${NC}"
    echo -e "      3. Open generated ${CYAN}storage/logs/xdebug/cachegrind.out.*${NC} in PhpStorm / VSCode / KCachegrind"
    echo ""
    echo -e "  ${MAGENTA}• Code Coverage for Testing:${NC}"
    echo -e "      Run: ${CYAN}./xdebug test-coverage --html${NC} -> open ${CYAN}storage/logs/coverage/index.html${NC}"
    echo ""
    echo -e "  ${MAGENTA}• Return to Production/Zero-Overhead:${NC}"
    echo -e "      Run: ${CYAN}./xdebug off${NC}"
    echo -e "${BOLD}${BLUE}=====================================================${NC}"
}

# Command Router
case "$1" in
    status)
        cmd_status
        ;;
    on)
        shift
        cmd_on "$@"
        ;;
    off)
        cmd_off
        ;;
    mode)
        shift
        cmd_mode "$@"
        ;;
    trigger)
        shift
        cmd_trigger "$@"
        ;;
    run-debug)
        shift
        cmd_run_debug "$@"
        ;;
    run-trace)
        shift
        cmd_run_trace "$@"
        ;;
    run-profile)
        shift
        cmd_run_profile "$@"
        ;;
    test-coverage|coverage)
        shift
        cmd_test_coverage "$@"
        ;;
    clean)
        cmd_clean
        ;;
    logs)
        shift
        cmd_logs "$@"
        ;;
    help|--help|-h|"")
        cmd_help
        ;;
    *)
        echo -e "${RED}Unknown command: $1${NC}"
        cmd_help
        exit 1
        ;;
esac
