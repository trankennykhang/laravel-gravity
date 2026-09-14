# Laravel Gravity Observability Stack

This folder contains the configuration files for the application's observability stack. The stack relies on OpenTelemetry for collecting data and the Grafana ecosystem for presentation and storage.

## Services Included

The `docker-compose.yaml` includes the following observability services:

- **OpenTelemetry Collector (`otel-collector`)**: The central receiver for telemetry data via OTLP (gRPC: `4317` / HTTP: `4318`). It routes data to Tempo, Prometheus, and Loki.
- **Prometheus (`:9090`)**: Scrapes and stores metrics data exposed by the OTel Collector (`:8889`).
- **Loki (`:3100`)**: Stores and indexes application log streams.
- **Tempo (`:3200`)**: Distributed tracing backend storing traces and spans.
- **Grafana (`:3001`)**: Visualization layer with pre-provisioned datasources and dashboard.

---

## Accessing Grafana

- **Grafana URL**: [http://localhost:3001](http://localhost:3001)
- **Authentication**: Anonymous access with Admin privileges is enabled for local development (no login needed).
- **Datasources**: Prometheus (`prometheus`), Loki (`loki`), and Tempo (`tempo`) are pre-provisioned with trace-to-logs correlation.
- **Pre-built Dashboard**: Navigate to **Dashboards** > **Laravel Gravity** > **[Laravel Gravity Observability](http://localhost:3001/d/laravel-gravity-overview)**.

---

## How to Test the Stack

You can generate telemetry (Traces, Metrics, and Logs) in two easy ways:

### Option 1: Via Artisan CLI

Run the custom verification command inside the container:

```bash
docker compose exec app php artisan telemetry:test
```

This command:
1. Creates a trace with nested spans (e.g., simulated database query and cache lookup) and sends it to **Tempo**.
2. Increments `test_counter_count_total` for **Prometheus**.
3. Emits an info log with Trace ID correlation to **Loki**.
4. Flushes the telemetry providers and prints direct Grafana Explore links.

### Option 2: Via Web Endpoint

Send an HTTP request in your browser or with curl:

```bash
curl http://localhost:8080/otel-test
```

Or visit `http://localhost:8080/otel-test` in your browser. The response contains the generated `trace_id` and pre-formatted Grafana links to jump directly to the trace, log, and metric views.

### Option 3: Normal Application Usage

Every normal web request to the Laravel app (e.g. `http://localhost:8080/` or `http://localhost:8080/login`) is automatically instrumented by `open-telemetry/opentelemetry-auto-laravel` and generates spans and HTTP traces in Tempo.

---

## How to View Telemetry Data in Grafana

Open [http://localhost:3001](http://localhost:3001) in your browser:

### 1. Pre-built Dashboard
- Go to **Dashboards** -> **Laravel Gravity Observability** (or [click here](http://localhost:3001/d/laravel-gravity-overview)).
- View real-time collector status, telemetry test counters, scrape duration graphs, and live Loki log streams.

### 2. Traces in Tempo
- Go to **Explore** (compass icon on the left menu) and select **Tempo** from the datasource dropdown.
- **Search**: Under the "Search" tab, select Service Name: `laravel-gravity-app` and click **Run query** to see recent traces.
- **TraceQL**: Enter a Trace ID directly (e.g., from `php artisan telemetry:test` or `/otel-test`) to see the full flamegraph and span waterfall.
- **Trace-to-Logs**: Clicking the document icon on any span jumps straight to related logs in Loki!

### 3. Logs in Loki
- Go to **Explore** and select **Loki** from the datasource dropdown.
- Run a LogQL query such as:
  ```logql
  {service_name=~".+"}
  ```
  or filter specifically for errors/info:
  ```logql
  {service_name="laravel-gravity-app"} |= "telemetry"
  ```

### 4. Metrics in Prometheus
- Go to **Explore** and select **Prometheus** from the datasource dropdown.
- Enter PromQL queries such as:
  ```promql
  test_counter_count_total
  up{job="otel-collector"}
  scrape_duration_seconds
  ```
