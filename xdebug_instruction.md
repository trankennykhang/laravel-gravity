# Xdebug Instruction & Integration Guide

This document outlines the Xdebug 3 integration for **Laravel Gravity**, detailing all modified and added files, configuration parameters, and practical usage workflows for **Step Debugging**, **Function Tracing**, **Performance Profiling**, and **Code Coverage Analysis**.

---

## 1. Summary of Changes

| Component / File | Purpose & Modifications |
|---|---|
| **[docker-compose.yaml](file:///data/www/php/laravel-gravity/docker-compose.yaml)** | • Added `extra_hosts: ["host.docker.internal:host-gateway"]` to `app` and `queue` services for Linux/Docker host resolution.<br>• Injected dynamic Xdebug environment variables (`XDEBUG_MODE`, `XDEBUG_CONFIG`, `XDEBUG_START_WITH_REQUEST`, `XDEBUG_IDEKEY`, `XDEBUG_TRIGGER_VALUE`).<br>• Mounted `./docker/php/xdebug.ini` and `./docker/php/local.ini` into `/usr/local/etc/php/conf.d/`. |
| **[docker/Dockerfile](file:///data/www/php/laravel-gravity/docker/Dockerfile)** | • Added `xdebug` extension to `install-php-extensions` during image build.<br>• Copied `docker/php/xdebug.ini` into the container image. |
| **[docker/php/xdebug.ini](file:///data/www/php/laravel-gravity/docker/php/xdebug.ini)** | • Created comprehensive Xdebug 3 configuration template covering all modes, port `9003`, client host `host.docker.internal`, uncompressed cachegrind profiler output, function tracing, and safe defaults. |
| **[.env](file:///data/www/php/laravel-gravity/.env) & [.env.example](file:///data/www/php/laravel-gravity/.env.example)** | • Added dedicated Xdebug 3 configuration block with clear comments for mode selection (`off`, `debug`, `trace`, `profile`, `coverage`, `develop`). |
| **[docker/xdebug.sh](file:///data/www/php/laravel-gravity/docker/xdebug.sh) & [./xdebug](file:///data/www/php/laravel-gravity/xdebug)** | • Created executable bash management tool (symlinked at root `./xdebug`) for turning Xdebug on/off, switching modes, running on-demand CLI tasks, and cleaning artifacts. |
| **[.vscode/launch.json](file:///data/www/php/laravel-gravity/.vscode/launch.json)** | • Added VS Code debug configurations with correct Docker path mappings (`/var/www/html` ↔ workspace root). |
| **[composer.json](file:///data/www/php/laravel-gravity/composer.json)** | • Added `test:coverage` (console summary) and `test:coverage-html` (interactive HTML report) scripts. |
| **[storage/logs/xdebug/](file:///data/www/php/laravel-gravity/storage/logs/xdebug/)** | • Output directory for Cachegrind snapshots, trace logs, and Xdebug logs with `.gitignore` to prevent repository pollution. |
| **[app/Http/Controllers/AuthController.php](file:///data/www/php/laravel-gravity/app/Http/Controllers/AuthController.php)** | • Corrected typo on line 13 (`ublic function` → `public function`). |

---

## 2. Managing Xdebug (Turn On / Off / Switch Modes)

You can manage Xdebug directly via the root `./xdebug` CLI utility:

```bash
# Display runtime status, active mode, configuration, and artifact file counts
./xdebug status

# Turn OFF Xdebug (sets XDEBUG_MODE=off; 0% performance overhead)
./xdebug off

# Turn ON Xdebug (defaults to step debugging mode: debug)
./xdebug on

# Switch to a specific mode or combination of modes
./xdebug mode debug         # Step debugging
./xdebug mode profile       # Performance profiling (Cachegrind)
./xdebug mode trace         # Function call tracing
./xdebug mode coverage      # Code coverage analysis
./xdebug mode develop       # Development helpers & var_dump enhancements
./xdebug mode debug,profile # Multiple concurrent modes
```

---

## 3. The 4 Core Capabilities & How to Use Them

### A. Step Debugging (`xdebug.mode = debug`)
Step debugging enables setting breakpoints, stepping over/into lines of code, inspecting call stacks, and evaluating live variables.

1. **Turn on debug mode**:
   ```bash
   ./xdebug on debug
   ```
2. **Configure your IDE**:
   - **VS Code**: Press `F5` or select **"Listen for Xdebug (Docker)"** from the Run & Debug tab.
   - **PhpStorm**:
     - Under `Settings > PHP > Servers`, ensure server `localhost` (port `8080`) has path mapping `/path/to/laravel-gravity` → `/var/www/html`.
     - Click **Start Listening for PHP Debug Connections** (telephone icon in top toolbar).
3. **Trigger debugging**:
   - **Browser**: Use the *Xdebug Helper* browser extension, or append `?XDEBUG_SESSION=PHPSTORM` to any URL.
   - **CLI / Artisan (Instant)**: Run with on-demand debugging without restarting containers:
     ```bash
     ./xdebug run-debug php artisan migrate
     ```

---

### B. Function Tracing (`xdebug.mode = trace`)
Records all function calls, parameter values, execution time, and memory changes to a human-readable trace file.

1. **Run an on-demand CLI trace**:
   ```bash
   ./xdebug run-trace php artisan route:list
   ```
2. **Trace Web Requests**:
   ```bash
   ./xdebug on trace
   # Send request with trigger parameter:
   curl "http://localhost:8080/my-endpoint?XDEBUG_TRIGGER=1"
   ```
3. **Trace outputs**:
   Generated trace logs are written to `storage/logs/xdebug/trace.*.xt`.

---

### C. Performance Profiling (`xdebug.mode = profile`)
Generates Cachegrind profiling files detailing execution time spent in every method, function, and database call.

1. **Run an on-demand CLI profile**:
   ```bash
   ./xdebug run-profile php artisan app:heavy-task
   ```
2. **Profile Web Requests**:
   ```bash
   ./xdebug on profile
   # Trigger profiling on any request:
   curl "http://localhost:8080/?XDEBUG_TRIGGER=1"
   ```
3. **Profile outputs & Visualization**:
   - Profile dumps are saved to `storage/logs/xdebug/cachegrind.out.*`.
   - **PhpStorm**: Open via `Tools > Analyze Xdebug Profiler Snapshot...` and select the file.
   - **VS Code**: View using extensions such as *PHP Profiler* or *Speedscope*.
   - **GUI Tools**: Open in [KCachegrind](https://kcachegrind.github.io/) (Linux) or [QCachegrind](https://sourceforge.net/projects/qcachegrindwin/) (macOS/Windows).

---

### D. Code Coverage Analysis (`xdebug.mode = coverage`)
Analyzes test suites to identify covered and uncovered lines, branches, and methods.

1. **Console Summary**:
   ```bash
   ./xdebug test-coverage
   # Or via composer:
   composer test:coverage
   ```
2. **Interactive HTML Report**:
   ```bash
   ./xdebug test-coverage --html
   # Or via composer:
   composer test:coverage-html
   ```
3. **View Report**:
   Open `storage/logs/coverage/index.html` in your web browser.

---

## 4. Maintenance & Cleaning

To clean up all generated trace files, profiler snapshots, logs, and coverage reports:
```bash
./xdebug clean
```

To tail the Xdebug diagnostic log:
```bash
./xdebug logs -f
```

---

## 5. Reference: Environment Variables

| Key | Default | Description |
|---|---|---|
| `XDEBUG_MODE` | `off` | Active Xdebug modes: `off`, `debug`, `trace`, `profile`, `coverage`, `develop` |
| `XDEBUG_CONFIG` | `client_host=host.docker.internal client_port=9003` | Host and port for IDE connection |
| `XDEBUG_START_WITH_REQUEST` | `trigger` | Trigger behavior: `trigger` (trigger parameter/cookie), `yes` (always), `no` |
| `XDEBUG_IDEKEY` | `PHPSTORM` | Session identifier for IDE debugging |
| `XDEBUG_TRIGGER_VALUE` | `""` | Optional secret trigger string |
