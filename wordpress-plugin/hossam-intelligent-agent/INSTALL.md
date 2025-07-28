# Installation Guide for Hossam Intelligent Agent WordPress Plugin

## نظرة عامة (Overview)
هذا الدليل يشرح كيفية تثبيت وتكوين إضافة المساعد الذكي لحسام في WordPress.

This guide explains how to install and configure the Hossam Intelligent Agent WordPress plugin.

## متطلبات النظام (System Requirements)

- WordPress 5.0 أو أحدث (WordPress 5.0 or newer)
- PHP 7.4 أو أحدث (PHP 7.4 or newer) 
- خادم المساعد الذكي الخلفي يعمل (Running Hossam Intelligent Agent backend server)

## خطوات التثبيت (Installation Steps)

### الخطوة 1: رفع الملفات (Step 1: Upload Files)

1. ارفع مجلد `hossam-intelligent-agent` إلى `/wp-content/plugins/`
   Upload the `hossam-intelligent-agent` folder to `/wp-content/plugins/`

2. أو استخدم واجهة WordPress الإدارية:
   Or use WordPress admin interface:
   - اذهب إلى الإضافات > إضافة جديد (Go to Plugins > Add New)
   - ارفع ملف ZIP (Upload ZIP file)

### الخطوة 2: تفعيل الإضافة (Step 2: Activate Plugin)

1. اذهب إلى الإضافات في لوحة تحكم WordPress
   Go to Plugins in WordPress admin dashboard

2. ابحث عن "Hossam Intelligent Agent"
   Find "Hossam Intelligent Agent"

3. انقر على "تفعيل" (Click "Activate")

### الخطوة 3: التكوين (Step 3: Configuration)

1. اذهب إلى الإعدادات > المساعد الذكي
   Go to Settings > Intelligent Agent

2. أدخل رابط الخادم الخلفي (Enter Backend URL):
   ```
   http://localhost:5000
   ```
   أو الرابط الخاص بخادمك (or your server URL)

3. أدخل مفتاح API (إذا كان مطلوباً)
   Enter API key (if required)

4. فعّل واجهة المحادثة
   Enable Chat Widget

5. انقر "حفظ التغييرات"
   Click "Save Changes"

## الاستخدام (Usage)

### إضافة واجهة المحادثة (Adding Chat Widget)

استخدم الكود المختصر التالي في أي صفحة أو منشور:
Use the following shortcode in any page or post:

```
[hia_chat]
```

### خيارات متقدمة (Advanced Options)

```
[hia_chat title="مساعد ذكي" height="500px" width="100%"]
```

المعاملات المتاحة (Available parameters):
- `title`: عنوان النافذة (Window title)
- `height`: ارتفاع النافذة (Window height) 
- `width`: عرض النافذة (Window width)

## اختبار الاتصال (Testing Connection)

### التحقق من عمل الخادم الخلفي (Check Backend Server)

1. تأكد من تشغيل خادم Python Flask:
   Make sure Python Flask server is running:
   ```bash
   cd /path/to/your/backend
   python main.py
   ```

2. اختبر الرابط في المتصفح:
   Test URL in browser:
   ```
   http://localhost:5000
   ```

### اختبار الإضافة (Test Plugin)

1. أضف `[hia_chat]` إلى صفحة اختبار
   Add `[hia_chat]` to a test page

2. اذهب إلى الصفحة وجرب إرسال رسالة
   Visit the page and try sending a message

3. تحقق من ظهور الرد من المساعد الذكي
   Check for AI assistant response

## حل المشاكل (Troubleshooting)

### مشاكل شائعة (Common Issues)

**المحادثة لا تعمل (Chat not working):**
- تحقق من رابط الخادم الخلفي في الإعدادات
  Check backend URL in settings
- تأكد من تشغيل الخادم الخلفي
  Ensure backend server is running

**أخطاء الاتصال (Connection errors):**
- تحقق من إعدادات الجدار الناري
  Check firewall settings
- تأكد من صحة المنفذ (Port)
  Verify correct port number

**مشاكل التصميم (Styling issues):**
- تحقق من تضارب CSS مع القالب
  Check for CSS conflicts with theme
- امسح ذاكرة التخزين المؤقت
  Clear cache

### تفعيل وضع التطوير (Enable Debug Mode)

أضف إلى ملف `wp-config.php`:
Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### سجلات الأخطاء (Error Logs)

تحقق من الملفات التالية:
Check these files:
- `/wp-content/debug.log`
- خادم الويب logs (Web server logs)
- سجلات خادم Python (Python server logs)

## الصيانة (Maintenance)

### التحديثات (Updates)

1. قم بعمل نسخة احتياطية من الموقع
   Backup your site

2. حمّل الإصدار الجديد
   Upload new version

3. اختبر الوظائف بعد التحديث
   Test functionality after update

### النسخ الاحتياطي (Backup)

احتفظ بنسخ احتياطية من:
Keep backups of:
- ملفات الإضافة (Plugin files)
- إعدادات قاعدة البيانات (Database settings)
- ملفات التخصيص (Customization files)

## الدعم (Support)

للحصول على المساعدة:
For support:
- GitHub: https://github.com/hossamhack7/HOSSAM-HASSAN
- تحقق من وثائق WordPress (Check WordPress documentation)
- راجع سجلات الأخطاء (Review error logs)