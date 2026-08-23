import { demoCountryOptions } from "@/data/marketplaceDemo";
import {
  clearLiveMarketplaceToken,
  createLiveAuction,
  createLiveProduct,
  getLiveMarketplaceToken,
  getLiveMarketplaceUser,
  getLiveSellerReferences,
  getLiveSellerProducts,
  getSavedMarketplaceCountryId,
  isLiveMarketplaceEnabled,
  loginLiveMarketplace,
  logoutLiveMarketplace,
  registerLiveMarketplace,
  submitLiveProductForReview,
  type LiveMarketplaceUser,
  type LiveSellerProduct,
  type SellerReferenceData,
  uploadLiveProductMedia,
} from "@/lib/marketplaceApi";
import { ArrowRight, CalendarClock, Check, ChevronLeft, FileImage, Gavel, ImagePlus, MapPin, ShieldCheck, Video } from "lucide-react";
import React, { FormEvent, useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Link } from "wouter";

const fallbackCities: Record<string, string[]> = {
  sa: ["الرياض", "جدة", "الدمام", "المدينة"],
  ae: ["دبي", "أبوظبي", "الشارقة"],
  eg: ["القاهرة", "الإسكندرية", "الجيزة"],
};

const fallbackCategories = ["ساعات ومجوهرات", "فن واقتناء", "تصوير وكاميرات", "تصميم وديكور", "سيارات", "تقنية"];
const conditionOptions = [
  { value: "new", label: "جديدة" },
  { value: "like_new", label: "مستعملة بحالة ممتازة" },
  { value: "good", label: "مستعملة بحالة جيدة" },
  { value: "fair", label: "تحتاج معاينة" },
  { value: "poor", label: "بحاجة إلى إصلاح" },
] as const;

type ConditionValue = (typeof conditionOptions)[number]["value"];

function toDatetimeLocalValue(date: Date) {
  const offset = date.getTimezoneOffset() * 60_000;
  return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

function suggestedAuctionWindow() {
  const start = new Date(Date.now() + 60 * 60_000);
  const end = new Date(start.getTime() + 24 * 60 * 60_000);
  return { start: toDatetimeLocalValue(start), end: toDatetimeLocalValue(end) };
}

function MediaPicker({ label, helper, accept, icon: Icon, files, onChange }: {
  label: string;
  helper: string;
  accept: string;
  icon: typeof ImagePlus;
  files: File[];
  onChange: (files: File[]) => void;
}) {
  return <label className="flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-[#143039]/20 bg-[#f4f3ed] p-5 text-center">
    <Icon className="text-[#d96d46]" />
    <span className="mt-3 text-sm font-semibold">{label}</span>
    <span className="mt-1 text-xs leading-5 text-[#6d7f83]">{files.length ? `${files.length} ملف محدد` : helper}</span>
    <input type="file" accept={accept} multiple={accept.includes("image")} className="sr-only" onChange={(event) => onChange(Array.from(event.target.files || []))} />
  </label>;
}

export default function SellSetup() {
  const [step, setStep] = useState<1 | 2>(1);
  const [country, setCountry] = useState("sa");
  const [city, setCity] = useState("الرياض");
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [category, setCategory] = useState(fallbackCategories[0]);
  const [condition, setCondition] = useState<ConditionValue>("good");
  const [imageFiles, setImageFiles] = useState<File[]>([]);
  const [videoFiles, setVideoFiles] = useState<File[]>([]);
  const [startingPrice, setStartingPrice] = useState("");
  const [reservePrice, setReservePrice] = useState("");
  const [increment, setIncrement] = useState("100");
  const [initialAuctionWindow] = useState(suggestedAuctionWindow);
  const [startTime, setStartTime] = useState(initialAuctionWindow.start);
  const [endTime, setEndTime] = useState(initialAuctionWindow.end);
  const [sellerReferences, setSellerReferences] = useState<SellerReferenceData | null>(null);
  const [sellerProducts, setSellerProducts] = useState<LiveSellerProduct[]>([]);
  const [selectedSellerProductId, setSelectedSellerProductId] = useState("");
  const [liveSessionUser, setLiveSessionUser] = useState<LiveMarketplaceUser | null>(null);
  const [isCheckingLiveSession, setIsCheckingLiveSession] = useState(true);
  const [accessMode, setAccessMode] = useState<"login" | "register">("login");
  const [loginEmail, setLoginEmail] = useState("");
  const [loginPassword, setLoginPassword] = useState("");
  const [isLoggingIn, setIsLoggingIn] = useState(false);
  const [registrationName, setRegistrationName] = useState("");
  const [registrationEmail, setRegistrationEmail] = useState("");
  const [registrationPhone, setRegistrationPhone] = useState("");
  const [registrationPassword, setRegistrationPassword] = useState("");
  const [registrationConfirmation, setRegistrationConfirmation] = useState("");
  const [isRegistering, setIsRegistering] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isScheduling, setIsScheduling] = useState(false);
  const countryId = getSavedMarketplaceCountryId();
  const isLive = isLiveMarketplaceEnabled() && countryId > 0;
  const cities = useMemo(() => sellerReferences?.cities.map((item) => item.name) || fallbackCities[country] || [], [country, sellerReferences]);
  const categories = useMemo(() => sellerReferences?.categories.map((item) => item.name) || fallbackCategories, [sellerReferences]);
  const allMediaFiles = [...imageFiles, ...videoFiles];

  useEffect(() => {
    if (!isLive || !getLiveMarketplaceToken()) {
      setLiveSessionUser(null);
      setIsCheckingLiveSession(false);
      return;
    }

    let cancelled = false;
    setIsCheckingLiveSession(true);
    getLiveMarketplaceUser(countryId).then((user) => {
      if (!cancelled) setLiveSessionUser(user);
    }).catch(() => {
      clearLiveMarketplaceToken();
      if (!cancelled) setLiveSessionUser(null);
    }).finally(() => {
      if (!cancelled) setIsCheckingLiveSession(false);
    });

    return () => { cancelled = true; };
  }, [countryId, isLive]);

  useEffect(() => {
    if (!isLive) return;
    let cancelled = false;
    getLiveSellerReferences(countryId).then((references) => {
      if (cancelled) return;
      setSellerReferences(references);
      setCountry(references.country.code.toLowerCase());
      setCity(references.cities[0]?.name || "");
      setCategory(references.categories[0]?.name || "");
    }).catch(() => {
      if (!cancelled) toast.error("تعذر تحميل مراجع الدولة الحية. سيبقى النموذج في وضع العرض المؤقت.");
    });
    return () => { cancelled = true; };
  }, [countryId, isLive]);

  useEffect(() => {
    if (!isLive || !liveSessionUser) {
      setSellerProducts([]);
      setSelectedSellerProductId("");
      return;
    }

    let cancelled = false;
    getLiveSellerProducts(countryId).then((products) => {
      if (!cancelled) setSellerProducts(products);
    }).catch(() => {
      if (!cancelled) toast.error("تعذر تحميل منتجاتك الآن. تحقق من تسجيل الدخول والسوق المختار.");
    });

    return () => { cancelled = true; };
  }, [countryId, isLive, liveSessionUser]);

  const changeCountry = (nextCountry: string) => {
    setCountry(nextCountry);
    setCity(fallbackCities[nextCountry]?.[0] || "");
    setSellerReferences(null);
  };

  const continueToAuction = () => {
    if (!title.trim() || description.trim().length < 20) {
      toast.error("أكمل عنوان القطعة ووصفاً لا يقل عن 20 حرفاً أولاً.");
      return;
    }
    setStep(2);
  };

  const submitListing = async (event: FormEvent) => {
    event.preventDefault();
    if (!isLive || !sellerReferences || !liveSessionUser) {
      toast.success("تم حفظ مسودة العرض فقط", { description: "سجّل الدخول واتصل بالمنصة لإرسال المنتج للمراجعة وجدولة مزاد." });
      return;
    }
    const cityReference = sellerReferences.cities.find((item) => item.name === city);
    const categoryReference = sellerReferences.categories.find((item) => item.name === category);
    if (!cityReference || !categoryReference) {
      toast.error("اختر مدينة وفئة من مرجع الدولة الحي قبل الإرسال.");
      return;
    }

    setIsSubmitting(true);
    try {
      const product = await createLiveProduct(countryId, {
        city_id: cityReference.id,
        category_id: categoryReference.id,
        title: title.trim(),
        description: description.trim(),
        condition,
      });
      await Promise.all(allMediaFiles.map((file) => uploadLiveProductMedia(countryId, product.id, file)));
      await submitLiveProductForReview(countryId, product.id);
      toast.success("أُرسل المنتج للمراجعة", { description: "تُحفظ الوسائط بشكل خاص. لا يمكن جدولة المزاد إلا بعد اعتماد المنتج من المشرف." });
    } catch {
      toast.error("تعذر إرسال المنتج. تحقق من تسجيل الدخول والسوق والملفات المسموح بها.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const scheduleApprovedProduct = async (event: FormEvent) => {
    event.preventDefault();
    if (!isLive || !liveSessionUser) {
      toast.info("سجّل الدخول أولاً لتتمكن من جدولة المزاد.");
      return;
    }
    if (!approvedProducts.length) {
      toast.info("لا يوجد منتج معتمد جاهز للجدولة بعد. أرسل منتجك للمراجعة ثم انتظر اعتماده.");
      return;
    }
    const product = sellerProducts.find((item) => item.id === Number(selectedSellerProductId));
    if (!product || product.status !== "approved" || product.auction) {
      toast.error("اختر منتجاً معتمداً غير مرتبط بمزاد أولاً.");
      return;
    }
    if (!startingPrice || !increment || !startTime || !endTime) {
      toast.error("أكمل سعر البداية والزيادة وتوقيت البداية والنهاية للمنتج المعتمد.");
      return;
    }

    const startsAt = new Date(startTime);
    const endsAt = new Date(endTime);
    if (Number.isNaN(startsAt.getTime()) || Number.isNaN(endsAt.getTime()) || endsAt <= startsAt) {
      toast.error("اختر وقت نهاية لاحقاً لوقت بداية المزاد.");
      return;
    }

    setIsScheduling(true);
    try {
      const auction = await createLiveAuction(countryId, {
        product_id: product.id,
        starting_price: startingPrice,
        reserve_price: reservePrice || undefined,
        minimum_increment: increment,
        start_time: startsAt.toISOString(),
        end_time: endsAt.toISOString(),
      });
      setSellerProducts((products) => products.map((item) => item.id === product.id ? { ...item, auction } : item));
      toast.success("تمت جدولة المزاد", { description: "يظهر المزاد وفق وقت البدء وحالة النشر المعتمدة." });
    } catch {
      toast.error("تعذر جدولة المزاد. تحقق من اعتماد المنتج، صلاحية إنشاء المزادات، والتوقيتات المدخلة.");
    } finally {
      setIsScheduling(false);
    }
  };

  const loginToLiveMarketplace = async (event: FormEvent) => {
    event.preventDefault();
    if (!isLive) {
      toast.info("تعذر فتح الحساب الآن. حاول مرة أخرى بعد قليل.");
      return;
    }

    setIsLoggingIn(true);
    try {
      const user = await loginLiveMarketplace(countryId, loginEmail.trim(), loginPassword);
      setLiveSessionUser(user);
      setLoginPassword("");
      toast.success("تم تسجيل الدخول", { description: "يمكنك الآن إرسال المنتجات للمراجعة وجدولة المزادات المعتمدة." });
    } catch {
      toast.error("تعذر تسجيل الدخول. تحقق من البريد وكلمة المرور والسوق المختار.");
    } finally {
      setIsLoggingIn(false);
    }
  };

  const registerForLiveMarketplace = async (event: FormEvent) => {
    event.preventDefault();
    if (!isLive || !sellerReferences) {
      toast.info("تعذر فتح التسجيل الآن. حاول مرة أخرى بعد قليل.");
      return;
    }
    const cityReference = sellerReferences.cities.find((item) => item.name === city);
    if (!cityReference) {
      toast.error("اختر مدينة من قائمة الدولة الحية قبل إنشاء الحساب.");
      return;
    }
    if (registrationPassword !== registrationConfirmation) {
      toast.error("تأكيد كلمة المرور غير مطابق.");
      return;
    }

    setIsRegistering(true);
    try {
      const user = await registerLiveMarketplace(countryId, {
        city_id: cityReference.id,
        name: registrationName.trim(),
        email: registrationEmail.trim(),
        phone: registrationPhone.trim() || undefined,
        password: registrationPassword,
        password_confirmation: registrationConfirmation,
      });
      setLiveSessionUser(user);
      setRegistrationPassword("");
      setRegistrationConfirmation("");
      toast.success("تم إنشاء الحساب", { description: "يمكنك الآن إرسال المنتجات للمراجعة وجدولة المزادات المعتمدة." });
    } catch {
      toast.error("تعذر إنشاء الحساب. تحقق من البيانات والبريد والسوق المختار.");
    } finally {
      setIsRegistering(false);
    }
  };

  const logoutFromLiveMarketplace = async () => {
    try {
      await logoutLiveMarketplace(countryId);
    } catch {
      // The token is cleared locally by the adapter even if the API is unavailable.
    }
    setLiveSessionUser(null);
    setSellerProducts([]);
    setSelectedSellerProductId("");
    toast.info("تم تسجيل الخروج من هذا المتصفح.");
  };

  const dataSourceLabel = isLive && sellerReferences && liveSessionUser ? "حسابك متصل" : "عرض تجريبي غير منشور";
  const selectedCondition = conditionOptions.find((item) => item.value === condition)?.label || condition;
  const approvedProducts = sellerProducts.filter((product) => product.status === "approved" && !product.auction);

  return <main dir="rtl" className="min-h-screen bg-[#f7f6f1] text-[#143039]">
    <header className="market-container flex h-20 items-center justify-between">
      <Link href="/" className="font-serif text-2xl font-semibold">مِزَاد</Link>
      <Link href="/" className="flex items-center gap-2 text-sm font-bold"><ArrowRight size={17} />العودة إلى المزادات</Link>
    </header>
    <section className="market-container pb-20 pt-4"><div className="mx-auto max-w-5xl">
      <div className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs font-bold tracking-[.16em] text-[#d96d46]">مسار البائع</p><h1 className="mt-2 font-serif text-4xl md:text-5xl">ابدأ من القطعة، ثم جهّز مزادك.</h1><p className="mt-4 max-w-2xl text-sm leading-7 text-[#66777b]">ينشئ الربط الحي منتجاً ووسائط خاصة ثم يرسله للمراجعة. لا تصبح جدولة المزاد متاحة إلا بعد الاعتماد.</p></div><span className="rounded-full bg-[#fff0e8] px-3 py-2 text-xs font-bold text-[#c45e39]">{dataSourceLabel}</span></div>
      {isLive && <section className="mt-6 rounded-2xl border border-[#143039]/10 bg-white p-5 shadow-[0_8px_24px_rgba(20,48,57,.05)]"><div className="flex flex-wrap items-center justify-between gap-3"><div><p className="text-xs font-bold tracking-[.12em] text-[#d96d46]">حساب البائع</p><h2 className="mt-1 font-serif text-2xl">{isCheckingLiveSession ? "جارٍ التحقق من حسابك…" : liveSessionUser ? `متصل باسم ${liveSessionUser.name}` : "سجّل الدخول لتفعيل الإرسال"}</h2><p className="mt-1 text-xs leading-5 text-[#6d7f83]">نحافظ على تسجيل دخولك بأمان في هذا المتصفح.</p></div>{liveSessionUser && <button type="button" onClick={logoutFromLiveMarketplace} className="rounded-xl border border-[#143039]/15 px-4 py-2 text-sm font-bold">تسجيل الخروج</button>}</div>
        {!isCheckingLiveSession && !liveSessionUser && <div className="mt-5"><div className="flex flex-wrap items-center justify-between gap-3"><div className="flex rounded-xl bg-[#edf0e8] p-1 text-xs font-bold"><button type="button" onClick={() => setAccessMode("login")} className={`rounded-lg px-3 py-2 transition ${accessMode === "login" ? "bg-white text-[#143039] shadow-sm" : "text-[#64777b]"}`}>دخول</button><button type="button" onClick={() => setAccessMode("register")} className={`rounded-lg px-3 py-2 transition ${accessMode === "register" ? "bg-white text-[#143039] shadow-sm" : "text-[#64777b]"}`}>حساب جديد</button></div>{accessMode === "register" && <span className="text-xs text-[#6d7f83]">الدولة والمدينة: {city || "اختر المدينة"}</span>}</div>{accessMode === "login" ? <form onSubmit={loginToLiveMarketplace} className="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]"><label className="grid gap-1 text-xs font-bold">البريد الإلكتروني<input value={loginEmail} onChange={(event) => setLoginEmail(event.target.value)} type="email" required className="rounded-xl border border-[#143039]/15 px-3 py-2.5 text-sm font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-1 text-xs font-bold">كلمة المرور<input value={loginPassword} onChange={(event) => setLoginPassword(event.target.value)} type="password" required className="rounded-xl border border-[#143039]/15 px-3 py-2.5 text-sm font-normal outline-none focus:border-[#d96d46]" /></label><button type="submit" disabled={isLoggingIn} className="mt-auto rounded-xl bg-[#12313a] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60">{isLoggingIn ? "جارٍ الدخول…" : "تسجيل الدخول"}</button></form> : <form onSubmit={registerForLiveMarketplace} className="mt-4 grid gap-3 md:grid-cols-2"><label className="grid gap-1 text-xs font-bold">الاسم الكامل<input value={registrationName} onChange={(event) => setRegistrationName(event.target.value)} required minLength={2} className="rounded-xl border border-[#143039]/15 px-3 py-2.5 text-sm font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-1 text-xs font-bold">البريد الإلكتروني<input value={registrationEmail} onChange={(event) => setRegistrationEmail(event.target.value)} type="email" required className="rounded-xl border border-[#143039]/15 px-3 py-2.5 text-sm font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-1 text-xs font-bold">رقم الهاتف <span className="font-normal text-[#6d7f83]">اختياري</span><input value={registrationPhone} onChange={(event) => setRegistrationPhone(event.target.value)} type="tel" className="rounded-xl border border-[#143039]/15 px-3 py-2.5 text-sm font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-1 text-xs font-bold">كلمة المرور <span className="font-normal text-[#6d7f83]">12 حرفاً على الأقل</span><input value={registrationPassword} onChange={(event) => setRegistrationPassword(event.target.value)} type="password" required minLength={12} className="rounded-xl border border-[#143039]/15 px-3 py-2.5 text-sm font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-1 text-xs font-bold">تأكيد كلمة المرور<input value={registrationConfirmation} onChange={(event) => setRegistrationConfirmation(event.target.value)} type="password" required minLength={12} className="rounded-xl border border-[#143039]/15 px-3 py-2.5 text-sm font-normal outline-none focus:border-[#d96d46]" /></label><button type="submit" disabled={isRegistering} className="mt-auto rounded-xl bg-[#d96d46] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60">{isRegistering ? "جارٍ إنشاء الحساب…" : "إنشاء حساب"}</button></form>}</div>}
      </section>}
      <div className="mt-9 grid gap-8 lg:grid-cols-[.72fr_1.28fr]">
        <aside className="rounded-[1.5rem] bg-[#12313a] p-6 text-white"><p className="text-xs font-bold tracking-[.14em] text-[#c7dcae]">رحلة الإدراج</p><ol className="mt-7 space-y-5">{[[1, "تفاصيل القطعة", "العنوان والوصف والفئة والموقع"], [2, "إعداد المزاد", "السعر والتوقيت والحدود"]].map(([number, label, copy]) => <li key={number} className="flex gap-3"><span className={`grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold ${step === Number(number) ? "bg-[#d96d46] text-white" : step > Number(number) ? "bg-[#c7dcae] text-[#12313a]" : "bg-white/10 text-[#cfe0dc]"}`}>{step > Number(number) ? <Check size={15} /> : number}</span><div><h2 className="font-serif text-xl">{label}</h2><p className="mt-1 text-xs leading-5 text-[#cfe0dc]">{copy}</p></div></li>)}</ol><div className="mt-9 rounded-2xl bg-white/8 p-4 text-xs leading-6 text-[#cfe0dc]"><ShieldCheck className="mb-2 text-[#c7dcae]" size={18} />الحالة الحية تنتقل من مسودة إلى مراجعة واعتماد ثم مزاد حي وفق سياسات السوق والدولة.</div></aside>
        <form onSubmit={submitListing} className="rounded-[1.5rem] bg-white p-6 shadow-[0_12px_40px_rgba(20,48,57,.08)] md:p-8">
          {step === 1 ? <>
            <div className="flex items-center justify-between"><div><p className="text-xs font-bold text-[#d96d46]">الخطوة 1 من 2</p><h2 className="mt-1 font-serif text-3xl">تفاصيل القطعة</h2></div><FileImage className="text-[#d96d46]" /></div>
            <div className="mt-7 grid gap-5">
              <label className="grid gap-2 text-sm font-semibold">عنوان القطعة<input value={title} onChange={(event) => setTitle(event.target.value)} placeholder="مثال: ساعة ميكانيكية كلاسيكية" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label>
              <label className="grid gap-2 text-sm font-semibold">الوصف<textarea value={description} onChange={(event) => setDescription(event.target.value)} placeholder="صف حالة القطعة وتفاصيلها المهمة." rows={5} className="resize-none rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label>
              <div className="grid gap-5 sm:grid-cols-2"><label className="grid gap-2 text-sm font-semibold">الفئة<select value={category} onChange={(event) => setCategory(event.target.value)} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46]">{categories.map((item) => <option key={item}>{item}</option>)}</select></label><label className="grid gap-2 text-sm font-semibold">الحالة<select value={condition} onChange={(event) => setCondition(event.target.value as ConditionValue)} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46]">{conditionOptions.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select></label></div>
              <div className="grid gap-5 sm:grid-cols-2"><label className="grid gap-2 text-sm font-semibold"><span className="flex items-center gap-2"><MapPin size={15} />الدولة</span><select value={country} onChange={(event) => changeCountry(event.target.value)} disabled={isLive} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46] disabled:cursor-not-allowed disabled:opacity-60">{demoCountryOptions.map((option) => <option key={option.code} value={option.code}>{option.name}</option>)}</select></label><label className="grid gap-2 text-sm font-semibold">المدينة<select value={city} onChange={(event) => setCity(event.target.value)} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46]">{cities.map((item) => <option key={item}>{item}</option>)}</select></label></div>
              <div className="grid gap-4 sm:grid-cols-2"><MediaPicker label="صور القطعة" helper="JPEG أو PNG أو WEBP، حتى 50MB للملف" accept="image/jpeg,image/png,image/webp" icon={ImagePlus} files={imageFiles} onChange={setImageFiles} /><MediaPicker label="فيديو اختياري" helper="MP4 أو WEBM، حتى 50MB" accept="video/mp4,video/webm" icon={Video} files={videoFiles} onChange={setVideoFiles} /></div>
            </div>
            <button type="button" onClick={continueToAuction} className="mt-8 flex w-full items-center justify-center gap-2 rounded-xl bg-[#12313a] px-5 py-3.5 text-sm font-bold text-white">تابع إلى إعداد المزاد <ChevronLeft size={17} /></button>
          </> : <>
            <div className="flex items-center justify-between"><div><p className="text-xs font-bold text-[#d96d46]">الخطوة 2 من 2</p><h2 className="mt-1 font-serif text-3xl">إعدادات المزاد</h2></div><CalendarClock className="text-[#d96d46]" /></div>
            <div className="mt-7 grid gap-5 sm:grid-cols-2"><label className="grid gap-2 text-sm font-semibold">سعر البداية<input value={startingPrice} onChange={(event) => setStartingPrice(event.target.value)} inputMode="decimal" placeholder="مثال: 500" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">سعر الاحتياط <span className="text-xs font-normal text-[#758488]">اختياري</span><input value={reservePrice} onChange={(event) => setReservePrice(event.target.value)} inputMode="decimal" placeholder="مثال: 1000" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">أقل زيادة<input value={increment} onChange={(event) => setIncrement(event.target.value)} inputMode="decimal" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><div className="rounded-xl border border-[#143039]/10 bg-[#f4f3ed] p-4 text-sm leading-6 text-[#6b7d81]">التوقيتات مقترحة وتُحفظ في حالة النموذج. أرسل المنتج للمراجعة أولاً، ثم بعد اعتماده اختره وجدول المزاد. يجب أن يكون وقت النهاية بعد وقت البداية.</div><label className="grid gap-2 text-sm font-semibold sm:col-span-2">وقت البداية<input value={startTime} onChange={(event) => setStartTime(event.target.value)} type="datetime-local" lang="en-GB" dir="ltr" className="rounded-xl border border-[#143039]/15 px-4 py-3 text-left font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold sm:col-span-2">وقت النهاية<input value={endTime} onChange={(event) => setEndTime(event.target.value)} type="datetime-local" lang="en-GB" dir="ltr" className="rounded-xl border border-[#143039]/15 px-4 py-3 text-left font-normal outline-none focus:border-[#d96d46]" /></label></div>
            <div className="mt-7 rounded-2xl bg-[#edf0e8] p-4 text-sm leading-6 text-[#59706d]"><strong className="text-[#143039]">ملخص العرض:</strong> {title || "قطعة بلا عنوان"} · {category} · {city} · حالة {selectedCondition}. سيخضع المنتج الفعلي للمراجعة قبل جدولة المزاد.</div>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row"><button type="button" onClick={() => setStep(1)} className="rounded-xl border border-[#143039]/15 px-5 py-3 text-sm font-bold">العودة للتفاصيل</button><button type="submit" disabled={isSubmitting} className="flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#d96d46] px-5 py-3 text-sm font-bold text-white disabled:cursor-wait disabled:opacity-60"><Gavel size={17} />{isSubmitting ? "جارٍ الإرسال…" : isLive ? "إرسال المنتج للمراجعة" : "حفظ مسودة العرض"}</button></div>
          </>}
        </form>
      </div>
      {isLive && liveSessionUser && <section className="mt-8 rounded-[1.5rem] border border-[#143039]/10 bg-white p-6 shadow-[0_12px_40px_rgba(20,48,57,.06)] md:p-8">
        <div className="flex flex-wrap items-start justify-between gap-3"><div><p className="text-xs font-bold tracking-[.14em] text-[#d96d46]">بعد الاعتماد</p><h2 className="mt-1 font-serif text-3xl">منتجاتك الحية</h2><p className="mt-2 max-w-2xl text-sm leading-6 text-[#66777b]">يمكنك جدولة مزاد فقط لمنتج تملكه وحالته <strong>معتمد</strong> وليس له مزاد سابق. المنتجات في المراجعة أو المرفوضة تبقى للمتابعة داخل لوحة الإدارة.</p></div><span className="rounded-full bg-[#edf0e8] px-3 py-2 text-xs font-bold text-[#4e6967]">{sellerProducts.length} منتج</span></div>
        {sellerProducts.length === 0 ? <p className="mt-6 rounded-xl bg-[#f4f3ed] p-4 text-sm leading-6 text-[#66777b]">لا توجد منتجات حية لهذا الحساب في البلد المختار بعد. أرسل منتجاً للمراجعة أولاً، ثم عد هنا بعد اعتماده.</p> : <div className="mt-6 grid gap-3">{sellerProducts.map((product) => <button type="button" key={product.id} onClick={() => !product.auction && product.status === "approved" && setSelectedSellerProductId(String(product.id))} className={`flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4 text-right transition ${selectedSellerProductId === String(product.id) ? "border-[#d96d46] bg-[#fff5f0]" : "border-[#143039]/10 bg-[#f7f6f1]"} ${product.status === "approved" && !product.auction ? "cursor-pointer" : "cursor-default opacity-75"}`}><span><strong className="block text-sm">{product.title}</strong><span className="mt-1 block text-xs text-[#6d7f83]">{product.category?.name || "فئة غير محددة"} · {product.city?.name || "مدينة غير محددة"}</span></span><span className="rounded-full bg-white px-3 py-1 text-xs font-bold text-[#4e6967]">{product.auction ? `مرتبط بمزاد ${product.auction.status}` : product.status === "approved" ? "جاهز للجدولة" : product.status}</span></button>)}</div>}
        <form onSubmit={scheduleApprovedProduct} className="mt-7 rounded-2xl bg-[#12313a] p-5 text-white"><div className="flex items-center gap-2"><Gavel size={18} className="text-[#c7dcae]" /><h3 className="font-serif text-2xl">جدولة مزاد لمنتج معتمد</h3></div><p className="mt-2 text-xs leading-5 text-[#cfe0dc]">تُستخدم أسعار وتوقيتات إعداد المزاد أعلاه. اختر منتجاً معتمداً ثم أرسل طلب الجدولة.</p><div className="mt-5 grid gap-4 md:grid-cols-[1fr_auto]"><label className="grid gap-2 text-sm font-semibold">المنتج المعتمد<select value={selectedSellerProductId} onChange={(event) => setSelectedSellerProductId(event.target.value)} className="rounded-xl bg-white px-4 py-3 text-[#143039] outline-none"><option value="">اختر منتجاً معتمداً</option>{approvedProducts.map((product) => <option key={product.id} value={product.id}>{product.title}</option>)}</select></label><button type="submit" disabled={isScheduling || approvedProducts.length === 0} className="mt-auto flex h-[48px] items-center justify-center gap-2 rounded-xl bg-[#d96d46] px-5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"><Gavel size={17} />{isScheduling ? "جارٍ الجدولة…" : "جدولة المزاد"}</button></div></form>
      </section>}
    </div></section>
  </main>;
}
