# Laravel Gravity Observability Stack

This folder contains the configuration files for the application's observability stack. The stack relies on OpenTelemetry for collecting data and the Grafana ecosystem for presentation and storage.

## Services Included

The `docker-compose.yaml` has been updated to include the following services:

- **OpenTelemetry Collector (`otel-collector`)**: The central receiver for telemetry data via OTLP (gRPC/HTTP). It processes the incoming data and routes it to the appropriate storage backends.
- **Prometheus**: Scrapes and stores metrics data provided by the OTel Collector.
- **Loki**: Stores and indexes log data.
- **Tempo**: Stores distributed trace data.
- **Grafana**: The visualization layer for the stack.

## Accessing the Dashboards

- **Grafana** is exposed on `http://localhost:3000`. 
- Anonymous authentication is enabled with Admin privileges for local development, so you do not need to log in.
- Datasources for Prometheus, Loki, and Tempo are automatically provisioned and ready to use.

## Application Configuration

The Laravel `app` and `queue` services have been configured with the following `OTEL_*` environment variables in `docker-compose.yaml` to route their telemetry to the OTel Collector:

```env
OTEL_PHP_AUTOLOAD_ENABLED=true
OTEL_SERVICE_NAME=laravel-gravity-app # (or queue)
OTEL_TRACES_EXPORTER=otlp
OTEL_METRICS_EXPORTER=otlp
OTEL_LOGS_EXPORTER=otlp
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf
```

## Next Steps for PHP

To fully enable OpenTelemetry in the PHP application, ensure the following:

1. **PHP Extension**: Install the OpenTelemetry PHP extension (`ext-opentelemetry`) in your `docker/Dockerfile`.
2. **Composer Packages**: Require the necessary OpenTelemetry packages in your `composer.json` (for example, `open-telemetry/sdk`, `open-telemetry/opentelemetry-auto-laravel`, and `open-telemetry/exporter-otlp`).
