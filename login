#!/bin/bash

# A script to easily log into the docker containers for the Laravel Gravity project.
# Usage: ./login <service_name> [command...]

if [ -z "$1" ]; then
    echo "Usage: ./login <service> [command...]"
    echo ""
    echo "Available services:"
    echo "  app      (Laravel PHP-FPM application)"
    echo "  web      (Nginx web server)"
    echo "  db       (MySQL database)"
    echo "  redis    (Redis cache)"
    echo "  queue    (Laravel Queue Worker)"
    echo "  mailpit  (Mailpit email server)"
    exit 1
fi

SERVICE=$1
shift

if [ $# -eq 0 ]; then
    # No command provided, drop into an interactive shell
    SHELL_CMD="sh"
    case "$SERVICE" in
        app|queue|db)
            SHELL_CMD="bash"
            ;;
    esac
    echo "Logging into $SERVICE container using $SHELL_CMD..."
    docker compose exec "$SERVICE" "$SHELL_CMD"
else
    # Run the provided command
    docker compose exec "$SERVICE" "$@"
fi
