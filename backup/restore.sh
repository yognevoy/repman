#!/usr/bin/env bash
set -euo pipefail

# ============================================================
# Repman restore script
# Runs on the host server, outside Docker.
#
# Usage:
#   ./restore.sh --db   /var/backups/repman/db/repman_db_20260501_030000.sql.gz
#   ./restore.sh --files /var/backups/repman/files/repman_files_20260501_030000.tar.gz
# ============================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPMAN_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$REPMAN_DIR/.env.docker"

_env_get() { grep -m1 "^${1}=" "$ENV_FILE" | cut -d= -f2; }
POSTGRES_USER="$(_env_get POSTGRES_USER)"
POSTGRES_PASSWORD="$(_env_get POSTGRES_PASSWORD)"
POSTGRES_DB="$(_env_get POSTGRES_DB)"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

usage() {
    echo "Usage:"
    echo "  $0 --db    <path/to/repman_db_TIMESTAMP.sql.gz>"
    echo "  $0 --files <path/to/repman_files_TIMESTAMP.tar.gz>"
    exit 1
}

[[ $# -lt 2 ]] && usage

MODE="$1"
ARCHIVE="$2"

if [[ ! -f "$ARCHIVE" ]]; then
    echo "ERROR: file not found: $ARCHIVE" >&2
    exit 1
fi

case "$MODE" in
    --db)
        log "DB restore: $ARCHIVE → $POSTGRES_DB"
        read -r -p "This will DROP and recreate the database '$POSTGRES_DB'. Continue? [y/N] " confirm
        [[ "$confirm" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 0; }

        log "Dropping and recreating database ..."
        docker compose -f "$REPMAN_DIR/docker-compose.yml" exec -T \
            -e PGPASSWORD="$POSTGRES_PASSWORD" database \
            psql -U "$POSTGRES_USER" -c "DROP DATABASE IF EXISTS \"$POSTGRES_DB\";"
        docker compose -f "$REPMAN_DIR/docker-compose.yml" exec -T \
            -e PGPASSWORD="$POSTGRES_PASSWORD" database \
            psql -U "$POSTGRES_USER" -c "CREATE DATABASE \"$POSTGRES_DB\";"

        log "Restoring from dump ..."
        zcat "$ARCHIVE" | docker compose -f "$REPMAN_DIR/docker-compose.yml" exec -T \
            -e PGPASSWORD="$POSTGRES_PASSWORD" database \
            psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"

        log "DB restore complete. Run migrations if needed:"
        log "  docker compose exec app bin/console d:m:m --no-interaction"
        ;;

    --files)
        log "Files restore: $ARCHIVE → $REPMAN_DIR"
        read -r -p "This will overwrite var/repo and var/proxy in $REPMAN_DIR. Continue? [y/N] " confirm
        [[ "$confirm" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 0; }

        tar -xzf "$ARCHIVE" -C "$REPMAN_DIR"
        log "Files restore complete."
        ;;

    *)
        usage
        ;;
esac
