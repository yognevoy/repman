#!/usr/bin/env bash
set -euo pipefail

# ============================================================
# Repman backup script
# Runs on the host server, outside Docker.
#
# Usage:
#   ./backup.sh               — full backup (DB + files)
#   ./backup.sh --db-only     — database only
#   ./backup.sh --files-only  — package files only
#
# Cron example (daily at 03:00):
#   0 3 * * * /path/to/repman/backup/backup.sh >> /var/log/repman-backup.log 2>&1
# ============================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPMAN_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$REPMAN_DIR/.env.docker"

# --- Configuration (override via environment variables if needed) ---
BACKUP_DIR="${BACKUP_DIR:-/var/backups/repman}"
KEEP_DAYS="${KEEP_DAYS:-14}"
# Uncomment and configure to enable remote upload via rclone:
# RCLONE_REMOTE="${RCLONE_REMOTE:-}"        # e.g. "s3-repman:repman-backups"
# RCLONE_MAX_AGE="${RCLONE_MAX_AGE:-30d}"

# --- Parse credentials from .env.docker (without sourcing the whole file) ---
_env_get() { grep -m1 "^${1}=" "$ENV_FILE" | cut -d= -f2; }
POSTGRES_USER="$(_env_get POSTGRES_USER)"
POSTGRES_PASSWORD="$(_env_get POSTGRES_PASSWORD)"
POSTGRES_DB="$(_env_get POSTGRES_DB)"

if [[ -z "$POSTGRES_USER" || -z "$POSTGRES_DB" || -z "$POSTGRES_PASSWORD" ]]; then
    echo "ERROR: Could not read POSTGRES_USER, POSTGRES_PASSWORD or POSTGRES_DB from $ENV_FILE" >&2
    exit 1
fi

# --- Parse arguments ---
MODE="full"
case "${1:-}" in
    --db-only)    MODE="db" ;;
    --files-only) MODE="files" ;;
    "")           MODE="full" ;;
    *) echo "Unknown argument: $1. Use --db-only or --files-only." >&2; exit 1 ;;
esac

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
mkdir -p "$BACKUP_DIR/db" "$BACKUP_DIR/files"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

log "Starting backup (mode: $MODE)"
log "Project dir: $REPMAN_DIR"
log "Backup dir:  $BACKUP_DIR"

# --- Database backup ---
backup_db() {
    local out="$BACKUP_DIR/db/repman_db_${TIMESTAMP}.sql.gz"
    log "DB: dumping $POSTGRES_DB as $POSTGRES_USER ..."

    docker compose -f "$REPMAN_DIR/docker-compose.yml" exec -T \
        -e PGPASSWORD="$POSTGRES_PASSWORD" \
        database \
        pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB" \
        | gzip -6 > "$out"

    log "DB: saved $(du -sh "$out" | cut -f1) → $out"
}

# --- Package files backup ---
backup_files() {
    local out="$BACKUP_DIR/files/repman_files_${TIMESTAMP}.tar.gz"
    local targets=()

    [[ -d "$REPMAN_DIR/var/repo" ]]   && targets+=("var/repo")
    [[ -d "$REPMAN_DIR/var/proxy" ]]  && targets+=("var/proxy")

    if [[ ${#targets[@]} -eq 0 ]]; then
        log "FILES: nothing to archive (var/repo and var/proxy not found), skipping"
        return
    fi

    log "FILES: archiving ${targets[*]} ..."
    tar -czf "$out" -C "$REPMAN_DIR" "${targets[@]}"
    log "FILES: saved $(du -sh "$out" | cut -f1) → $out"
}

# --- Run selected mode ---
[[ "$MODE" == "full" || "$MODE" == "db" ]]    && backup_db
[[ "$MODE" == "full" || "$MODE" == "files" ]] && backup_files

# --- Cleanup old local backups ---
log "Cleanup: removing backups older than ${KEEP_DAYS} days ..."
find "$BACKUP_DIR" -name "*.gz" -mtime +"$KEEP_DAYS" -delete
log "Cleanup: done"

# --- Remote upload via rclone (optional) ---
if [[ -n "${RCLONE_REMOTE:-}" ]]; then
    log "Rclone: uploading to $RCLONE_REMOTE ..."
    rclone copy "$BACKUP_DIR" "$RCLONE_REMOTE" \
        --max-age "${RCLONE_MAX_AGE:-30d}" \
        --transfers 4 \
        --log-level INFO
    log "Rclone: done"
fi

log "Backup complete"
