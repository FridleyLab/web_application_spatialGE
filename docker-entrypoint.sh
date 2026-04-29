#!/bin/bash
set -e

# --- Docker socket permissions ---
# The host Docker socket is mounted into this container.
# Detect its GID and add www-data to a matching group so
# Laravel (running as www-data) can spawn sibling containers.
DOCKER_SOCKET=/var/run/docker.sock
if [ -S "$DOCKER_SOCKET" ]; then
    DOCKER_GID=$(stat -c '%g' "$DOCKER_SOCKET")
    # Add www-data to a group that owns the Docker socket.
    # First check if a group with this GID already exists (e.g., GID 0 = root on macOS hosts).
    EXISTING_GROUP=$(getent group "$DOCKER_GID" | cut -d: -f1)
    if [ -n "$EXISTING_GROUP" ]; then
        # GID already taken — just add www-data to that group
        usermod -aG "$EXISTING_GROUP" www-data
    elif getent group docker >/dev/null 2>&1; then
        # 'docker' group exists but with a different GID — adjust it
        groupmod -g "$DOCKER_GID" docker
        usermod -aG docker www-data
    else
        # No group with this GID and no 'docker' group — create one
        groupadd -g "$DOCKER_GID" docker
        usermod -aG docker www-data
    fi
fi

# --- Laravel storage permissions ---
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# --- Set worker count for supervisor ---
if [ "$APP_ENV" = "production" ]; then
    export WORKER_COUNT=8
else
    export WORKER_COUNT=2
fi

# --- Start supervisord (manages Apache + queue workers) ---
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
