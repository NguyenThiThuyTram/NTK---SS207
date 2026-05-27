#!/bin/bash
# scripts/deploy-db.sh - Tự động thiết lập database và chạy migration trên server

# 1. Nhập database gốc
chmod +x scripts/import-db.sh
./scripts/import-db.sh

if [ $? -eq 0 ]; then
    echo "🔄 Đang chạy database migrations inside Docker..."
    docker compose exec -T php php /var/www/html/run_migration.php
    docker compose exec -T php php /var/www/html/run_loyalty_migration.php
    echo "✅ Toàn bộ quá trình thiết lập Database đã hoàn tất thành công!"
else
    echo "❌ Thiết lập database thất bại."
    exit 1
fi
