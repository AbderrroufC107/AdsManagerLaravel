#!/bin/bash
# ========================================
# سكربت النشر التلقائي
# ========================================
# شغّل هذا السكربت بعد كل تحديث للمشروع
# ========================================

echo "=== نشر التحديثات ==="

cd /home/thikmknr/adsmanager

# 1. سحب أحدث التحديثات
echo "1. سحب التحديثات..."
git pull origin main

# 2. تثبيت المكتبات
echo "2. تثبيت المكتبات..."
composer install --no-dev --optimize-autoloader --prefer-dist

# 3. تشغيل الترحيل
echo "3. تشغيل الترحيل..."
php artisan migrate --force

# 4. إعطاء الصلاحيات
echo "4. إعداد الصلاحيات..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 5. مسح الكاش
echo "5. مسح الكاش..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo ""
echo "=== تم النشر بنجاح! ==="
