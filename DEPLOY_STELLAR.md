# دليل نشر مشروع Laravel على استضافة Namecheap Stellar

## المتطلبات

1. **حساب استضافة Stellar** من Namecheap
2. **SSH مفعّل** (يفعّل من cPanel → Manage Shell)
3. **PHP 8.2+** (يجب التأكد من الإصدار في cPanel → Select PHP Version)
4. **الدومين** متصل بالاستضافة

---

## الخطوة 1: تجهيز المشروع محلياً

```bash
cd C:\xampp\htdocs\AdsManagerLaravel

# تثبيت المكتبات
composer install --optimize-autoloader --no-dev

# إنشاء ملف .env (إذا لم يكن موجوداً)
copy .env.example .env

# توليد مفتاح التطبيق
php artisan key:generate
```

---

## الخطوة 2: رفع الملفات عبر cPanel

### الطريقة: رفع ملف مضغوط

1. أدخل على **cPanel → File Manager**
2. اذهب إلى المجلد الرئيسي (عادة `public_html` أو `/home/thikmknr/`)
3. اضغط **Upload** وارفع ملف `adsmanager.zip`
4. اضغط **Extract** لفك الضغط

### الملفات اللي تحتاجها:

```
AdsManagerLaravel/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/          ← (بعد composer install)
├── .env
├── .env.example
├── artisan
├── composer.json
└── composer.lock
```

### الملفات اللي تحذفها:

```
tests/
node_modules/
.git/
.env.example
phpunit.xml
README.md
DEPLOY_STELLAR.md
```

---

## الخطوة 3: تجهيز المشروع على السيرفر

### الدخول على SSH

```bash
ssh thikmknr@server-name -p 21098
```

**ملاحظة:** استبدل `server-name` باسم السيرفر الخاص بك (تجده في cPanel → Account Information → Server Information)

### تثبيت المكتبات

```bash
cd /home/thikmknr/adsmanager

composer install --optimize-autoloader --no-dev
```

### إعداد ملف .env

```bash
cp .env.example .env
nano .env
```

أضف هذه القيم:

```env
APP_NAME=AdsManager
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=sqlite
DB_DATABASE=/home/thikmknr/adsmanager/database/database.sqlite

SESSION_DRIVER=database
CACHE_STORE=database

MASTER_KEY=ここに64文字の16進数キー
ANTHROPIC_API_KEY=your_anthropic_key
META_ACCESS_TOKEN=your_meta_token
META_ACCOUNT_ID=act_your_account_id
ADSET_DAILY_BUDGET_CEILING=50000
DAILY_TOTAL_CHANGE_CEILING=100000
```

### توليد المفتاح

```bash
php artisan key:generate
```

### إنشاء قاعدة البيانات

```bash
touch database/database.sqlite
php artisan migrate --force
```

### إعطاء صلاحيات الكتابة

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod 644 .env
chmod 600 database/database.sqlite
```

### تحسين الأداء

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## الخطوة 4: تجهيز الدومين

### الطريقة A: تغيير Document Root (مُوصى بها)

1. في cPanel، اذهب إلى **Domains** أو **Addon Domains**
2. ابحث عن دومينك واضغط **Manage**
3. غيّر **Document Root** إلى:
   ```
   /home/thikmknr/adsmanager/public
   ```
4. احفظ التغييرات

### الطريقة B: استخدام .htaccess

إذا لم تتمكن من تغيير Document Root، أنشئ ملف `.htaccess` في المجلد الأصلي:

```bash
cd /home/thikmknr
nano public_html/.htaccess
```

أضف:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # تحويل كل شيء إلى مجلد public
    RewriteCond %{REQUEST_URI} !^/adsmanager/public/
    RewriteRule ^(.*)$ /adsmanager/public/$1 [L]
</IfModule>
```

---

## الخطوة 5: تفعيل SSL

1. في cPanel، اذهب إلى **Security → SSL/TLS Status**
2. اختر دومينك واضغط **Run AutoSSL**
3. انتظر حتى يكتمل التثبيت

---

## الخطوة 6: اختبار المشروع

1. افتح متصفحك واذهب إلى:
   ```
   https://yourdomain.com/login
   ```

2. يجب أن ترى صفحة تسجيل الدخول

3. لتسجيل الدخول، يجب إنشاء حساب أولاً عبر SSH:

```bash
cd /home/thikmknr/adsmanager
php artisan tinker
```

ثم اكتب:

```php
App\Services\AuthService::createUser('admin', 'your_password_here');
```

اضغط `Ctrl + D` للخروج

4. الآن سجّل الدخول بالاسم وكلمة المرور

---

## استكشاف الأخطاء

### الخطأ: 500 Internal Server Error

```bash
# تحقق من صلاحيات الملفات
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# تحقق من ملف .env
cat .env

# تحقق من السجلات
cat storage/logs/laravel.log
```

### الخطأ: Database not found

```bash
touch database/database.sqlite
php artisan migrate --force
```

### الخطأ: Permission denied

```bash
chmod 644 .env
chmod 600 database/database.sqlite
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### الخطأ: Class not found

```bash
composer dump-autoload
composer install --optimize-autoloader --no-dev
```

---

## النسخ الاحتياطي

### نسخ احتياطي لقاعدة البيانات

```bash
cp /home/thikmknr/adsmanager/database/database.sqlite /home/thikmknr/backups/adsmanager_$(date +%Y%m%d).sqlite
```

### نسخ احتياطي كامل

```bash
cd /home/thikmknr
tar -czf backups/adsmanager_$(date +%Y%m%d).tar.gz adsmanager/
```

---

## تحديث المشروع

### رفع ملفات جديدة

1. ارفع الملفات الجديدة عبر cPanel File Manager
2. الدخول على SSH وتشغيل:

```bash
cd /home/thikmknr/adsmanager
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ملاحظات مهمة

1. **قاعدة البيانات SQLite**: تعمل بشكل جيد على الاستضافة المشتركة، لكن قد يكون هناك مشاكل مع الكتابات المتزامنة

2. **الأمان**: لا تشارك ملف `.env` أبداً

3. **الأداء**: استضافة Stellar محدودة (512MB RAM، CPU مشترك) - قد تكون بطيئة خلال أوقات الذروة

4. **النسخ الاحتياطي**: اعمل نسخة احتياطية بشكل دوري لقاعدة البيانات

5. **التحديثات**: عند تحديث Laravel أو المكتبات، اختبر محلياً أولاً
