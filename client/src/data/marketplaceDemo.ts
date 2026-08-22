/**
 * بيانات عرض مؤقتة للواجهة فقط. لا تمثل مزادات أو طلبات أو مستخدمين حقيقيين.
 * استبدالها لاحقاً يكون عبر محولات Laravel API، لا عبر مكونات الصفحات.
 * لا يحتوي هذا المصدر عمداً على مراجعات أو تقييمات أو شهادات مستخدمين.
 */
export type DemoAuction = {
  id: number;
  category: string;
  title: string;
  city: string;
  countryCode: string;
  price: string;
  time: string;
  paint: string;
  mark: string;
  condition: string;
  description: string;
  sellerLabel: string;
  shippingLabel: string;
};

export const demoCountryOptions = [
  { apiId: 1, code: "sa", name: "المملكة العربية السعودية", currency: "ر.س" },
  { apiId: 2, code: "ae", name: "الإمارات العربية المتحدة", currency: "د.إ" },
  { apiId: 3, code: "eg", name: "جمهورية مصر العربية", currency: "ج.م" },
] as const;

export const demoAuctionLots: DemoAuction[] = [
  { id: 1, category: "ساعات ومجوهرات", title: "ساعة كرونوغراف كلاسيكية", city: "الرياض", countryCode: "sa", price: "12,800 ر.س", time: "01:42:18", paint: "from-[#10242c] via-[#466978] to-[#e5d0a1]", mark: "⌁", condition: "مستعملة بحالة ممتازة", description: "قطعة عرض مؤقتة لواجهة المزاد، مع تفاصيل منظمة قابلة للاستبدال ببيانات المنتج الحقيقية.", sellerLabel: "بائع تجريبي موثّق", shippingLabel: "تسليم منظم داخل السوق" },
  { id: 2, category: "فن واقتناء", title: "تكوين معاصر، إصدار محدود", city: "جدة", countryCode: "sa", price: "7,250 ر.س", time: "03:15:42", paint: "from-[#ce6a4b] via-[#f1c46f] to-[#183d58]", mark: "◒", condition: "محفوظة بعناية", description: "قطعة عرض مؤقتة لواجهة المزاد، مع تفاصيل منظمة قابلة للاستبدال ببيانات المنتج الحقيقية.", sellerLabel: "بائع تجريبي موثّق", shippingLabel: "استلام أو شحن منظم" },
  { id: 3, category: "تصوير وكاميرات", title: "كاميرا فيلمية احترافية", city: "الدمام", countryCode: "sa", price: "4,600 ر.س", time: "غداً", paint: "from-[#292a2d] via-[#6e5c47] to-[#c8b69c]", mark: "◉", condition: "مفحوصة للعرض", description: "قطعة عرض مؤقتة لواجهة المزاد، مع تفاصيل منظمة قابلة للاستبدال ببيانات المنتج الحقيقية.", sellerLabel: "بائع تجريبي موثّق", shippingLabel: "شحن محمي عند الربط" },
  { id: 4, category: "تصميم وديكور", title: "مصباح إيطالي من السبعينات", city: "المدينة", countryCode: "sa", price: "3,980 ر.س", time: "05:06:11", paint: "from-[#754631] via-[#bd8565] to-[#e5cfb2]", mark: "◌", condition: "معاين للعرض", description: "قطعة عرض مؤقتة لواجهة المزاد، مع تفاصيل منظمة قابلة للاستبدال ببيانات المنتج الحقيقية.", sellerLabel: "بائع تجريبي موثّق", shippingLabel: "تسليم منظم داخل السوق" },
];

export const demoBidActivity = [
  { amount: "12,800 ر.س", time: "منذ دقيقتين", label: "مزايد تجريبي #184" },
  { amount: "12,500 ر.س", time: "منذ 7 دقائق", label: "مزايد تجريبي #052" },
  { amount: "12,200 ر.س", time: "منذ 13 دقيقة", label: "مزايد تجريبي #619" },
];

export const demoAccountSnapshot = {
  watchedCount: 4,
  activeBidCount: 2,
  wallet: { available: "3,450 ر.س", pending: "1,280 ر.س", label: "محفظة عرض مؤقتة" },
  orders: [
    { reference: "ORD-DEMO-1038", title: "تكوين معاصر، إصدار محدود", status: "بانتظار إتمام الدفع", progress: "خطوة 1 من 3", tone: "amber" },
    { reference: "ORD-DEMO-1022", title: "كاميرا فيلمية احترافية", status: "قيد تجهيز الشحن", progress: "خطوة 2 من 3", tone: "teal" },
  ],
  notifications: [
    { title: "تم تجاوز مزايدتك", detail: "تحديث عرض مؤقت لمزاد ساعة كرونوغراف.", time: "منذ دقيقتين" },
    { title: "تحديث في حالة الطلب", detail: "أصبح الطلب التجريبي جاهزاً لخطوة الشحن.", time: "منذ 28 دقيقة" },
  ],
} as const;

export const demoAccountSummaryCards = [
  { key: "watchlist", label: "محفوظاتي التجريبية", value: demoAccountSnapshot.watchedCount, description: "مزادات محفوظة للمتابعة" },
  { key: "active-bids", label: "مزايداتي النشطة", value: demoAccountSnapshot.activeBidCount, description: "مزادات ما زالت مفتوحة" },
] as const;

export function getDemoCountry(code: string) {
  return demoCountryOptions.find((country) => country.code === code);
}

export function getDemoAuction(id: string | number) {
  return demoAuctionLots.find((auction) => auction.id === Number(id)) ?? demoAuctionLots[0];
}
