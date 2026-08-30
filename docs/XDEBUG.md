# Xdebug Management Guide for Laravel Gravity

This project includes complete support for **Xdebug 3** inside Docker, allowing you to turn Xdebug on/off at will and seamlessly switch between **Step Debugging**, **Function Tracing**, **Performance Profiling**, and **Code Coverage Analysis**.

---

## Quick Reference CLI (`./xdebug`)

You can manage all Xdebug capabilities via the `./xdebug` CLI script in the project root:

```bash
# Check status, active mode, configuration, and generated artifacts
./xdebug status

# Turn ON Xdebug (default mode: debug)
./xdebug on

# Turn OFF Xdebug (0% performance overhead for normal development)
./xdebug off

# Switch to a specific mode or combination of modes
./xdebug mode debug
./xdebug mode profile
./xdebug mode trace
./xdebug mode coverage
./xdebug mode develop
./xdebug mode debug,profile

# Run one-off CLI commands with on-demand Xdebug (no container restart required)
./xdebug run-debug php artisan migrate
./xdebug run-trace php artisan test
./xdebug run-profile php artisan route:list
./xdebug test-coverage --html

# Clean up all generated trace, profile, and coverage files
./xdebug clean
```

---

## 1. Step Debugging (`debug`)

Step debugging allows you to set breakpoints in your IDE, inspect variables, evaluate expressions, and step through code execution.

### How to use:
1. **Enable debug mode**:
   ```bash
   ./xdebug on debug
   ```
2. **Configure your IDE** to listen on port `9003`:
   - **VS Code**: Use the pre-configured launcher in `.vscode/launch.json` ("Listen for Xdebug (Docker)").
   - **PhpStorm**:
     - Go to `Settings > PHP > Servers`.
     - Add server: Name `localhost`, Host `localhost`, Port `8080` (or `APP_PORT`).
     - Enable **Use path mappings**: Map project root `/path/to/laravel-gravity` to `/var/www/html`.
     - Click **Start Listening for PHP Debug Connections** (phone icon).
3. **Trigger debugging**:
   - **Web requests**: Use the [Xdebug Helper browser extension](https://xdebug.org/docs/step_debug#browser-extensions) or append `?XDEBUG_SESSION=PHPSTORM` (or `?XDEBUG_SESSION=VSCODE`) to your URL.
   - **CLI / Artisan commands**: Run via `./xdebug run-debug <command>` (e.g. `./xdebug run-debug php artisan tinker`).

---

## 2. Function Tracing (`trace`)

Function tracing records every function call, arguments, return values, execution timing, and memory deltas to disk.

### How to use:
1. **Option A — On-demand CLI trace**:
   ```bash
   ./xdebug run-trace php artisan route:list
   ```
2. **Option B — Web request trace**:
   ```bash
   ./xdebug on trace
   # Make a web request with: http://localhost:8080/my-endpoint?XDEBUG_TRIGGER=1
   ```
3. **Output location**:
   Traces are saved to `storage/logs/xdebug/trace.*.xt`.

---

## 3. Performance Profiling (`profile`)

Performance profiling generates standard Cachegrind files that capture call trees, execution counts, CPU time, and memory usage per function.

### How to use:
1. **Option A — On-demand CLI profiling**:
   ```bash
   ./xdebug run-profile php artisan app:heavy-task
   ```
2. **Option B — Web request profiling**:
   ```bash
   ./xdebug on profile
   # Make a web request with: http://localhost:8080/my-endpoint?XDEBUG_TRIGGER=1
   ```
3. **Output location**:
   Profile snapshots are saved to `storage/logs/xdebug/cachegrind.out.*`.
4. **Visualizing profiles**:
   - **PhpStorm**: Go to `Tools > Analyze Xdebug Profiler Snapshot...` and select the generated `cachegrind.out.*` file.
   - **VS Code**: Use extensions like *PHP Profiler* or *Speedscope*.
   - **Standalone GUI**: [KCachegrind](https://kcachegrind.github.io/) (Linux/KDE) or [QCachegrind](https://sourceforge.net/projects/qcachegrindwin/) (Windows/macOS).

---

## 4. Code Coverage Analysis (`coverage`)

Code coverage determines exactly which lines and branches of code are executed during automated tests.

### How to use:
1. **Run test suite with console coverage summary**:
   ```bash
   ./xdebug test-coverage
   # Or via composer:
   composer test:coverage
   ```
2. **Generate interactive HTML code coverage report**:
   ```bash
   ./xdebug test-coverage --html
   # Or via composer:
   composer test:coverage-html
   ```
3. **View report**:
   Open `storage/logs/coverage/index.html` in any web browser.

---

## Configuration via Environment Variables

All settings are defined in `.env` and injected via `docker-compose.yaml`:

| Variable | Default | Description |
|---|---|---|
| `XDEBUG_MODE` | `off` | Active modes: `off`, `debug`, `trace`, `profile`, `coverage`, `develop` |
| `XDEBUG_START_WITH_REQUEST` | `trigger` | Triggering: `trigger` (cookie/param), `yes` (always), `no` |
| `XDEBUG_CONFIG` | `client_host=host.docker.internal client_port=9003` | Client connection parameters |
| `XDEBUG_IDEKEY` | `PHPSTORM` | Session / IDE key |
| `XDEBUG_TRIGGER_VALUE` | `""` | Secret value for trigger matching (optional) |
