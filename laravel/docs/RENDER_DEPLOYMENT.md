# نشر منصة Auction Marketplace على Render

## النطاق

يتضمن الملف الجذري `render.yaml` خمس خدمات تشغيلية: واجهة Laravel العامة، عامل Horizon، عامل Scheduler، خدمة Reverb العامة للـWebSocket، وخدمة Meilisearch خاصة، إضافة إلى PostgreSQL وKey Value متوافق مع Redis/Valkey. يجب أن تكون هذه الموارد في منطقة Render واحدة؛ يضبط الـBlueprint المنطقة على `frankfurt` ويمكن تغييرها قبل الإنشاء إذا كانت منطقة البيانات المطلوبة مختلفة.

> النشر يستهدف Laravel API وخدماته. واجهة Marketplace المنشورة حالياً يجب أن تحصل لاحقاً على `APP_URL` و`CORS_ALLOWED_ORIGINS` الصحيحين وتُبدّل مصدر العرض المؤقت بمحولات API الحية.

## قبل الإنشاء

1. ارفع المشروع إلى مستودع Git خاص أو عام مرتبط بحساب Render. يبقى `render.yaml` في جذر المستودع، بينما يستخدم كل Service سياق البناء `laravel/`.
2. أنشئ Blueprint جديداً من المستودع، ثم راجع أن الخدمات هي: `auction-api` و`auction-horizon` و`auction-scheduler` و`auction-reverb` و`auction-meilisearch` و`auction-queue` و`auction-postgres`.
3. وفر كل القيم التي يطلبها Render بعلامة `sync: false`. أهمها `APP_KEY` و`APP_URL` والنطاقات الخاصة بـSanctum وCORS وAWS/S3 وبيانات البريد واسم مضيف Reverb العام.
4. أنشئ `APP_KEY` محلياً بواسطة `php artisan key:generate --show` وانسخ القيمة التي تبدأ بـ`base64:`. لا تستخدم قيمة متوقعة أو مولّدة عشوائياً بلا بادئة Laravel.

## متغيرات الواجهة والهوية

| المتغير | القيمة المطلوبة |
|---|---|
| `APP_URL` | رابط خدمة `auction-api` العام، مثل `https://api.example.com`. |
| `FRONTEND_URL` | رابط واجهة Marketplace، مثل `https://app.example.com`. |
| `CORS_ALLOWED_ORIGINS` | روابط الواجهات المسموح بها مفصولة بفاصلة، من دون مسار. |
| `SANCTUM_STATEFUL_DOMAINS` | المضيفات فقط، مثل `app.example.com,api.example.com`، من دون `https://`. |
| `SESSION_DOMAIN` | نطاق مشترك مثل `.example.com` عند استخدام نطاقات فرعية؛ اتركه فارغاً عند عدم وجود نطاق مشترك. |
| `REVERB_HOST` | نطاق خدمة `auction-reverb` العام من دون بروتوكول. |

## الأوامر التشغيلية

| الخدمة | الأمر الذي يشغله Blueprint | الغرض |
|---|---|---|
| Laravel API | `/usr/local/bin/render-web` | Nginx + PHP-FPM ويستمع إلى `PORT` المخصص من Render. |
| Horizon | `php artisan horizon` | معالجة قوائم Redis والـbroadcasting والإشعارات المفهرسة. |
| Scheduler | `php artisan schedule:work` | تنفيذ `auctions:start-due` و`auctions:close-expired` والمهام المجدولة. |
| Reverb | `php artisan reverb:start --host=0.0.0.0 --port="$PORT"` | اتصالات WebSocket العامة. |
| Meilisearch | صورة `getmeili/meilisearch:v1.13` خاصة مع قرص دائم. | فهرسة Scout والبحث. |

يجري `php artisan migrate --force` كأمر ما قبل النشر لخدمة API فقط. لا تشغّل الترحيلات من Horizon أو Scheduler أو Reverb حتى لا تتنافس الخدمات على ترحيل واحد.

## التخزين والبيانات

استخدم S3 أو مزود S3-compatible خارجي للإنتاج. لا تستخدم قرص حاوية Render كوسيط الوسائط؛ نظام الملفات مؤقت. يجب أن يشير `AWS_BUCKET` إلى Bucket خاص، وأن يعالج Laravel الروابط الموقعة للوصول إلى الوسائط الخاصة.

تستخدم خدمة Key Value سياسة `noeviction` مع Journal + Snapshot لحماية قوائم Horizon من الإخلاء. لا تغيّر هذه السياسة إلى سياسة cache لأن ذلك قد يفقد Jobs غير معالجة.

## ما بعد النشر

1. افتح `https://<api-domain>/up` للتحقق من صحة خدمة Laravel.
2. من Shell خدمة API نفّذ `php artisan horizon:status` و`php artisan scout:sync-index-settings`، ثم أعد فهرسة المحتوى المعتمد عند وجوده.
3. أنشئ أو اعتمد الدول والعملات والمدن والفئات وصلاحيات الفريق. لا تُدخل بيانات دفع أو مزودي شحن حقيقيين قبل إنهاء أسرارهم وتحقق Webhook.
4. حدّث إعداد واجهة Marketplace بعنوان API العام، ثم استبدل `marketplaceDemo.ts` بمحول API تدريجي، مع إرسال `X-Marketplace-Country` رقمياً في كل طلب مقيد بالسوق.

## تنبيهات قبل الإطلاق

لا يعني إنشاء خدمات Render أن الدفعات أو الشحن أو المزاد المالي أصبح جاهزاً تلقائياً. ما زال يجب اختيار بوابة تدعم Mada/Visa/Mastercard/Apple Pay حسب الدولة، إضافة التحقق الحقيقي لتوقيع Webhook، واختيار مزودي الشحن SPL/Aramex/DHL وربط أسرارهم. كما يلزم إنهاء اختبار تنازع مزايدات متعدد العمال ومراجعة الأمان قبل فتح التنفيذ المالي للمستخدمين.

## مراجع رسمية

- [1] [Render Blueprint YAML Reference](https://render.com/docs/blueprint-spec)
- [2] [Render Key Value](https://render.com/docs/key-value)
- [3] [Docker on Render](https://render.com/docs/docker)
