#!/bin/bash
# ========================================
# سكربت تجهيز استضافة Namecheap للنشر
# ========================================
# شغّل هذا السكربت مرة واحدة فقط عبر SSH
# ========================================

echo "=== تجهيز الاستضافة للنشر ==="

# 1. الانتقال لمجلد المشروع
cd /home/thikmknr

# 2. إنشاء مجلد المشروع إذا لم يكن موجوداً
if [ ! -d "adsmanager" ]; then
    mkdir -p adsmanager
    echo "تم إنشاء مجلد adsmanager"
fi

# 3. الانتقال للمجلد
cd adsmanager

# 4. تثبيت Composer إذا لم يكن مثبتاً
if ! command -v composer &> /dev/null; then
    echo "تثبيت Composer..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

# 5. تثبيت المكتبات
echo "تثبيت المكتبات..."
composer install --no-dev --optimize-autoloader --prefer-dist

# 6. إعداد ملف .env
if [ ! -f ".env" ]; then
    echo "إعداد ملف .env..."
    cp .env.example .env
    php artisan key:generate --force
    echo ""
    echo "⚠️  مهم: قم بتعديل ملف .env وأضف مفاتيحك"
    echo "nano .env"
fi

# 7. إنشاء قاعدة البيانات
echo "إنشاء قاعدة البيانات..."
touch database/database.sqlite
php artisan migrate --force

# 8. إعطاء الصلاحيات
echo "إعداد الصلاحيات..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod 644 .env
chmod 600 database/database.sqlite

# 9. تحسين الأداء
echo "تحسين الأداء..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo ""
echo "=== تم تجهيز الاستضافة بنجاح! ==="
echo ""
echo "الآن:"
echo "1. عدّل ملف .env وأضف مفاتيحك:"
echo "   nano .env"
echo ""
echo "2. غيّر Document Root في cPanel إلى:"
echo "   /home/thikmknr/adsmanager/public"
echo ""
echo "3. افتح متصفحك واذهب إلى:"
echo "   https://yourdomain.com/login"
echo ""
echo "4. لإنشاء حساب مستخدم:"
echo "   php artisan tinker"
echo "   ثم اكتب:"
echo "   App\Services\AuthService::createUser('admin', 'your_password')"
echo "   ثم اضغط Ctrl+D للخروج"
