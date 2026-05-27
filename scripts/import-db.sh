#!/bin/bash
# scripts/import-db.sh - Nhập database từ file backup ntk_backup.sql vào server

source ~/NTK---SS207/.env

echo "🔄 Đang nhập database vào MySQL ($DB_NAME)..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < ~/NTK---SS207/ntk_backup.sql

if [ $? -eq 0 ]; then
    echo "✅ Nhập database thành công!"
else
    echo "❌ Nhập database thất bại. Vui lòng kiểm tra lại cấu hình."
fi
