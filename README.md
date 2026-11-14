# متابعة المشاريع الحكومية

نظام لرصد وتتبع جميع المشاريع في المنطقة عبر منصة محكمة وبشكل مباشر.

تعرض المنصة حالة المشروع، ونسبة الإنجاز، والموعد المتوقع، والموقع على الخريطة، والتنبيهات للمشاريع التي تحتاج متابعة. يشمل السجل مشاريع الوكالات الثلاث: التحول الرقمي، والاستراتيجية، والتميز المؤسسي، للأعوام 2024 و2025 و2026.

## التشغيل المحلي

1. شغّل Apache وMySQL من XAMPP، أو شغّل `start-server.bat` ثم افتح MySQL.
2. انسخ `config/config.example.php` إلى `config/config.php` وعدّل بيانات الاتصال إن لزم.
3. افتح [http://localhost:8080/install.php](http://localhost:8080/install.php) لبناء القاعدة والبيانات التجريبية.
4. المنصة: [http://localhost:8080](http://localhost:8080)

دخول لوحة التحكم للتجربة: `admin` / `Admin@2026`

## المعاينة على GitHub

الواجهة العامة تُعرض من GitHub Pages:

https://moneerafahaid-collab.github.io/government-projects-tracking/

لوحة التحكم لإضافة المشاريع تعمل محليًا فقط، لأن GitHub لا يشغّل PHP.

## التقنيات

HTML، CSS، JavaScript، PHP، MySQL.
