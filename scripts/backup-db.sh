#!/bin/bash
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="/tmp/ntk_backup_${TIMESTAMP}.sql.gz"

source ~/NTK---SS207/.env
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_FILE"
aws s3 cp "$BACKUP_FILE" "s3://ntk-fashion-backups/daily/"
rm -f "$BACKUP_FILE"
echo "✅ Backup done: ${TIMESTAMP}"
