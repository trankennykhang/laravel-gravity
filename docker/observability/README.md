# Local observability

Start the stack with `docker compose up -d`. Grafana is available at
`http://localhost:3001` by default; the example development credentials are
`admin` / `admin`. Change `GRAFANA_ADMIN_PASSWORD` before exposing it outside a
local machine.

Grafana provisions these data sources automatically:

- Prometheus (`http://localhost:9090`) for collector, host, and container metrics.
- Loki (`http://localhost:3100`) for Laravel log files sent by Promtail.
- Tempo (`http://localhost:3200`) for traces received through the OpenTelemetry Collector.

The Laravel app and queue worker export OTLP to `otel-collector:4318` within
Compose. For a process running on the host, use `http://localhost:4318`.

The host and Docker mounts used by cAdvisor and node-exporter are intended for
local development. Do not expose their endpoints or use this Compose topology
as-is in production.
