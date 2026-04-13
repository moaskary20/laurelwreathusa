# laurelwreathusa

تطبيق محاسبي مبني على [Laravel](https://laravel.com) ولوحة [Filament](https://filamentphp.com) متعددة المستأجرين (Companies).

**المستودع:** [github.com/moaskary20/laurelwreathusa](https://github.com/moaskary20/laurelwreathusa)

## المتطلبات

- PHP **^8.2**
- Composer
- Node.js و npm (لبناء الأصول الأمامية)

## التشغيل المحلي

```bash
git clone https://github.com/moaskary20/laurelwreathusa.git
cd laurelwreathusa
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

بديلًا يمكن استخدام سكربت الإعداد الجاهز في المشروع (يُثبّت الحزم، ينشئ `.env` إن لزم، يولّد المفتاح، يشغّل الهجرات، ثم `npm install` و `npm run build`):

```bash
composer run setup
```

لوحة التحكم: عادةً `http://127.0.0.1:8000/admin` (مع معرّف الشركة في المسار حسب إعداد Filament).

## خطوات الرفع إلى GitHub

بعد تعديل الملفات محليًا:

```bash
cd laurelwreathusa
git status
git add -A
git commit -m "وصف التغييرات باختصار"
git push origin main
```

إن كان الفرع الحالي `main` ومتتبعًا لـ `origin/main`، يكفي:

```bash
git push
```

**ملاحظة:** لا ترفع ملف `.env` (مستثنى في `.gitignore`). استخدم `.env.example` كمرجع.

## الاختبارات

```bash
composer test
```

## الترخيص

مشروع Laravel الأساسي مرخّص تحت [MIT](https://opensource.org/licenses/MIT).
