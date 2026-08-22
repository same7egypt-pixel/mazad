# Auction Marketplace Platform

منصة مزادات متعددة الدول مبنية بـ **Laravel 13 / PHP 8.3**. تحتوي على نطاقات للمنتجات والمزادات والمزايدات والطلبات والمدفوعات والمحافظ والشحن والبحث والإشعارات والحوكمة. الواجهة العربية العامة موجودة في جذر المستودع ضمن `client/` وتستخدم بيانات عرض مؤقتة حتى يتم إعداد عنوان Laravel API قابل للوصول من المتصفح.

## البنية

| المكوّن | الاستخدام |
|---|---|
| Laravel + PostgreSQL | قواعد المجال والمعاملات وسياق الدولة والـAPI. |
| Redis / Horizon | القوائم، jobs، البث، الجلسات والكاش. |
| Reverb | تحديثات المزادات الفورية. |
| Meilisearch / Scout | فهرسة والبحث المقيد بالسوق. |
| S3-compatible storage | وسائط المنتجات الخاصة. |
| Spatie Permission / Activitylog | الصلاحيات وتسجيل الأنشطة والتدقيق. |

## التطوير المحلي

### المتطلبات

يلزم Docker Compose وDocker Engine، إضافة إلى Git. استخدم القيم المحلية في `docker/local.env.template` للتطوير فقط؛ لا تنسخها إلى الإنتاج.

```bash
cd laravel
cp docker/local.env.template .env
docker compose up -d --build
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan horizon:status
```

تعمل الخدمات المحلية بالمسارات التالية عادةً: Laravel عبر Nginx على `http://localhost:8000`، Reverb على `http://localhost:8080`، Meilisearch على `http://localhost:7700`، وMinIO على `http://localhost:9000`.

## الاختبارات

تستعمل اختبارات Laravel PostgreSQL للتحقق من السلوك الذي يعتمد أقفال الصفوف والمعاملات.

```bash
php artisan test
./vendor/bin/pint --dirty
composer audit
```

تشمل التغطية الحالية دورة المزاد، المزايدة الذرية، تسوية المدفوعات وإعادة Webhook، الشحن، عزل البلد، البحث، الإشعارات، الحوكمة، واكتشاف الدول النشطة. اختبار التنازع المتوازي بين عدة Workers ما زال جزءاً من تقوية ما قبل الإطلاق.

## API والسياق متعدد الدول

تتطلب أغلب مسارات API الترويسة الرقمية التالية:

```http
X-Marketplace-Country: 1
```

يمكن للواجهة أولاً استدعاء `GET /api/marketplaces/countries` لمعرفة الدول النشطة، ثم إرسال `id` المختار في كل طلب مقيّد بالسوق. لا تفترض معرف دولة في الواجهة.

## نشر Render

يتضمن جذر المستودع `render.yaml` لإعداد:

- خدمة Laravel API عامة؛
- Worker لـHorizon؛
- Worker للـScheduler؛
- خدمة Reverb WebSocket عامة؛
- Meilisearch خاص مع قرص دائم؛
- Render Key Value متوافق مع Redis/Valkey؛
- PostgreSQL.

استخدم `Dockerfile.render` في `laravel/` لجميع خدمات Laravel. راجع [RENDER_DEPLOYMENT.md](./docs/RENDER_DEPLOYMENT.md) للخطوات، ومراجعة المتغيرات، وأوامر الترحيل، والتخزين، والنطاقات، والإجراءات اللاحقة للنشر. يوضح `.env.production.example` شكل المتغيرات المطلوبة من دون أسرار حقيقية.

## حالة المنتج وحدود الإطلاق

توجد بنية مزاد حقيقية وخدمات خلفية واختبارات، لكن لا تعتبر المنصة جاهزة لتشغيل أموال أو شحن حقيقي قبل الآتي:

1. تحديد دولة/دول الإطلاق، بوابة دفع تدعم الوسائل المطلوبة، ومزودي الشحن.
2. إعداد أسرار الإنتاج وتحقق توقيع Webhook الفعلي لكل مزود.
3. ربط الواجهة العامة بعنوان Laravel API وSanctum وCORS وReverb.
4. إنهاء لوحة إدارة Filament، إدارة مرجعيات الدول، وإدارة الوسائط الكاملة.
5. تنفيذ اختبار تنازع مزايدات متعدد العمال، مراجعة أمنية، واختبارات أداء قبل فتح التنفيذ المالي.

لا تحتوي بيانات العرض الحالية على تقييمات أو مراجعات مستخدمين مزيفة. يشرح [FRONTEND_DEMO_DATA.md](./docs/FRONTEND_DEMO_DATA.md) خطة استبدال بيانات العرض ببيانات Laravel الحية.
