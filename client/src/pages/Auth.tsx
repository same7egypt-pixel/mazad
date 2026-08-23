import {
  getLiveMarketplaceCountries,
  getLiveSellerReferences,
  isLiveMarketplaceEnabled,
  LaravelApiRequestError,
  loginLiveMarketplace,
  registerLiveMarketplace,
  saveMarketplaceCountryId,
  type MarketplaceCountry,
  type SellerReferenceData,
} from "@/lib/marketplaceApi";
import { ArrowRight, KeyRound, RefreshCw, UserPlus } from "lucide-react";
import React, { FormEvent, useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Link, useLocation } from "wouter";

type AccessMode = "login" | "register";

export default function Auth() {
  const [, navigate] = useLocation();
  const [mode, setMode] = useState<AccessMode>("login");
  const [countries, setCountries] = useState<MarketplaceCountry[]>([]);
  const [selectedCountryId, setSelectedCountryId] = useState(0);
  const [references, setReferences] = useState<SellerReferenceData | null>(null);
  const [selectedCityId, setSelectedCityId] = useState(0);
  const [isLoadingReferences, setIsLoadingReferences] = useState(true);
  const [marketplaceLoadAttempt, setMarketplaceLoadAttempt] = useState(0);
  const [marketplaceLoadError, setMarketplaceLoadError] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const selectedCountry = useMemo(
    () => countries.find((country) => country.id === selectedCountryId) || null,
    [countries, selectedCountryId],
  );

  useEffect(() => {
    if (!isLiveMarketplaceEnabled()) {
      setIsLoadingReferences(false);
      return;
    }

    let cancelled = false;
    setMarketplaceLoadError(false);
    setIsLoadingReferences(true);
    getLiveMarketplaceCountries().then((items) => {
      if (cancelled) return;
      setCountries(items);
      setSelectedCountryId(items[0]?.id || 0);
    }).catch(() => {
      if (!cancelled) {
        setIsLoadingReferences(false);
        setMarketplaceLoadError(true);
        toast.error("تعذر الاتصال بخدمة الحسابات. حاول مرة أخرى بعد إيقاظ الخادم.");
      }
    });

    return () => { cancelled = true; };
  }, [marketplaceLoadAttempt]);

  useEffect(() => {
    if (!selectedCountryId || !isLiveMarketplaceEnabled()) return;

    let cancelled = false;
    setIsLoadingReferences(true);
    setReferences(null);
    getLiveSellerReferences(selectedCountryId).then((data) => {
      if (cancelled) return;
      setMarketplaceLoadError(false);
      setReferences(data);
      setSelectedCityId(data.cities[0]?.id || 0);
    }).catch(() => {
      if (!cancelled) {
        setMarketplaceLoadError(true);
        toast.error("تعذر تحميل المدن المتاحة لهذا السوق.");
      }
    }).finally(() => {
      if (!cancelled) setIsLoadingReferences(false);
    });

    return () => { cancelled = true; };
  }, [selectedCountryId]);

  const handleLogin = async (event: FormEvent) => {
    event.preventDefault();
    if (!selectedCountryId) return;

    setIsSubmitting(true);
    try {
      const user = await loginLiveMarketplace(selectedCountryId, email.trim(), password);
      saveMarketplaceCountryId(selectedCountryId);
      toast.success(`مرحباً ${user.name}`, { description: "تم تسجيل الدخول إلى حسابك بنجاح." });
      navigate("/account");
    } catch (error) {
      if (error instanceof LaravelApiRequestError) {
        const emailErrors = error.validationErrors.email || [];
        const rejection = emailErrors.join(" ").toLowerCase();

        if (error.status === 429) {
          toast.error("تكررت محاولات الدخول بسرعة. انتظر قليلاً ثم أعد المحاولة.");
        } else if (/unavailable|marketplace|غير متاح|السوق/.test(rejection)) {
          toast.error("هذا الحساب غير متاح في السوق المحدد.", { description: "اختر السوق الذي أنشأت فيه الحساب ثم أعد المحاولة." });
        } else {
          toast.error("بيانات الدخول غير صحيحة.", { description: "تحقق من البريد الإلكتروني وكلمة المرور ثم أعد المحاولة." });
        }
      } else {
        toast.error("تعذر الاتصال بخدمة الحسابات. حاول مرة أخرى بعد إيقاظ الخادم.");
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleRegistration = async (event: FormEvent) => {
    event.preventDefault();
    if (!selectedCountryId || !selectedCityId) {
      toast.error("اختر سوقاً ومدينة قبل إنشاء الحساب.");
      return;
    }
    if (password !== passwordConfirmation) {
      toast.error("تأكيد كلمة المرور غير مطابق.");
      return;
    }

    setIsSubmitting(true);
    try {
      const user = await registerLiveMarketplace(selectedCountryId, {
        city_id: selectedCityId,
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim() || undefined,
        password,
        password_confirmation: passwordConfirmation,
      });
      saveMarketplaceCountryId(selectedCountryId);
      toast.success(`أهلاً ${user.name}`, { description: "تم إنشاء الحساب وتسجيل دخولك." });
      navigate("/account");
    } catch (error) {
      if (error instanceof LaravelApiRequestError) {
        const emailErrors = error.validationErrors.email || [];
        const existingEmail = error.status === 422 && /already|taken|exists|موجود|مسجل/i.test(emailErrors.join(" "));
        if (existingEmail) {
          setMode("login");
          setPassword("");
          setPasswordConfirmation("");
          toast.message("هذا البريد مسجل بالفعل.", { description: "انتقلنا بك إلى تسجيل الدخول؛ أدخل كلمة المرور فقط." });
        } else if (error.status === 429) {
          toast.error("تكررت المحاولات بسرعة. انتظر قليلاً ثم أعد المحاولة.");
        } else {
          const firstValidationError = Object.values(error.validationErrors).flat()[0];
          toast.error(firstValidationError || error.message || "تعذر إنشاء الحساب. تحقق من الحقول ثم أعد المحاولة.");
        }
      } else {
        toast.error("تعذر إنشاء الحساب بسبب مشكلة اتصال. أعد المحاولة بعد لحظات.");
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const unavailable = !isLiveMarketplaceEnabled();
  const retryMarketplaceLoading = () => {
    setCountries([]);
    setSelectedCountryId(0);
    setSelectedCityId(0);
    setReferences(null);
    setMarketplaceLoadAttempt((attempt) => attempt + 1);
  };

  return <main dir="rtl" className="min-h-screen bg-[#f7f6f1] px-4 py-8 text-[#143039] sm:py-14">
    <section className="mx-auto max-w-5xl overflow-hidden rounded-[2rem] bg-white shadow-[0_24px_70px_rgba(20,48,57,.12)] lg:grid lg:grid-cols-[.85fr_1.15fr]">
      <aside className="bg-[#12313a] p-8 text-white md:p-12">
        <Link href="/" className="inline-flex items-center gap-2 text-sm font-bold text-[#c7dcae]"><ArrowRight size={17} /> العودة للمزادات</Link>
        <p className="mt-16 text-xs font-bold tracking-[.16em] text-[#c7dcae]">حساب مِزَاد</p>
        <h1 className="mt-4 font-serif text-4xl leading-tight md:text-5xl">ادخل للمزايدة، أو ابدأ البيع بثقة.</h1>
        <p className="mt-6 max-w-md text-sm leading-7 text-[#cfe0dc]">أنشئ حساباً واحداً لمتابعة المزادات والطلبات والمحفظة، ثم أضف مقتنياتك للمراجعة عند جاهزيتها.</p>
        <div className="mt-12 rounded-2xl border border-white/10 bg-white/8 p-5 text-sm leading-6 text-[#cfe0dc]">نحافظ على تسجيل دخولك بأمان في هذا المتصفح. لا تشارك كلمة المرور في أي رسالة أو قناة دعم.</div>
      </aside>
      <section className="p-7 md:p-12">
        <div className="flex rounded-xl bg-[#edf0e8] p-1 text-sm font-bold">
          <button type="button" onClick={() => setMode("login")} className={`flex-1 rounded-lg px-4 py-2.5 transition ${mode === "login" ? "bg-white text-[#12313a] shadow-sm" : "text-[#64777b]"}`}>تسجيل الدخول</button>
          <button type="button" onClick={() => setMode("register")} className={`flex-1 rounded-lg px-4 py-2.5 transition ${mode === "register" ? "bg-white text-[#12313a] shadow-sm" : "text-[#64777b]"}`}>حساب جديد</button>
        </div>
        <div className="mt-8"><p className="text-xs font-bold tracking-[.14em] text-[#d96d46]">{mode === "login" ? "دخول آمن" : "تسجيل مستخدم جديد"}</p><h2 className="mt-2 font-serif text-3xl">{mode === "login" ? "مرحباً بعودتك" : "أنشئ حسابك"}</h2></div>
        {unavailable ? <div className="mt-7 rounded-2xl border border-[#d96d46]/30 bg-[#fff5f0] p-4 text-sm leading-6 text-[#9d4027]">خدمة الحسابات غير مهيأة حالياً. حاول لاحقاً أو تواصل مع الإدارة.</div> : <>
          <div className="mt-7 grid gap-4 sm:grid-cols-2"><label className="grid gap-2 text-sm font-semibold">السوق<select value={selectedCountryId} onChange={(event) => setSelectedCountryId(Number(event.target.value))} disabled={!countries.length || isLoadingReferences} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46] disabled:opacity-60">{countries.map((country) => <option key={country.id} value={country.id}>{country.name}</option>)}</select></label><div className="grid gap-2 text-sm font-semibold"><span>السوق النشط</span><div className="rounded-xl border border-[#143039]/10 bg-[#f4f3ed] px-4 py-3 font-normal text-[#64777b]">{isLoadingReferences ? "يجري تجهيز السوق؛ قد يستغرق أول فتح أقل من دقيقة." : selectedCountry?.currency?.symbol || selectedCountry?.currency?.code || "غير محدد"}</div></div></div>
          {marketplaceLoadError ? <div className="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#d96d46]/25 bg-[#fff5f0] p-4 text-sm leading-6 text-[#8c3d2a]"><span>تعذر تجهيز السوق الآن. يمكنك إعادة المحاولة.</span><button type="button" onClick={retryMarketplaceLoading} className="inline-flex items-center gap-2 rounded-xl border border-[#d96d46]/30 bg-white px-3 py-2 font-bold text-[#9d4027] transition hover:bg-[#fff0e8]"><RefreshCw size={15} />إعادة تحميل السوق</button></div> : null}
          {mode === "login" ? <form onSubmit={handleLogin} className="mt-6 grid gap-4"><label className="grid gap-2 text-sm font-semibold">البريد الإلكتروني<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required autoComplete="email" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">كلمة المرور<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required autoComplete="current-password" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><button type="submit" disabled={isSubmitting || !selectedCountryId} className="mt-2 flex items-center justify-center gap-2 rounded-xl bg-[#12313a] px-5 py-3.5 text-sm font-bold text-white disabled:opacity-60"><KeyRound size={17} />{isSubmitting ? "جارٍ الدخول…" : "تسجيل الدخول"}</button></form> : <form onSubmit={handleRegistration} className="mt-6 grid gap-4"><label className="grid gap-2 text-sm font-semibold">الاسم الكامل<input value={name} onChange={(event) => setName(event.target.value)} required minLength={2} autoComplete="name" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">المدينة<select value={selectedCityId} onChange={(event) => setSelectedCityId(Number(event.target.value))} disabled={isLoadingReferences || !references} required className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46] disabled:opacity-60">{references?.cities.map((city) => <option key={city.id} value={city.id}>{city.name}</option>)}</select></label><label className="grid gap-2 text-sm font-semibold">البريد الإلكتروني<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required autoComplete="email" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">رقم الهاتف <span className="font-normal text-[#6d7f83]">اختياري</span><input value={phone} onChange={(event) => setPhone(event.target.value)} type="tel" autoComplete="tel" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">كلمة المرور <span className="font-normal text-[#6d7f83]">12 حرفاً على الأقل</span><input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required minLength={12} autoComplete="new-password" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">تأكيد كلمة المرور<input value={passwordConfirmation} onChange={(event) => setPasswordConfirmation(event.target.value)} type="password" required minLength={12} autoComplete="new-password" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><button type="submit" disabled={isSubmitting || isLoadingReferences || !selectedCityId} className="mt-2 flex items-center justify-center gap-2 rounded-xl bg-[#d96d46] px-5 py-3.5 text-sm font-bold text-white disabled:opacity-60"><UserPlus size={17} />{isSubmitting ? "جارٍ إنشاء الحساب…" : "إنشاء الحساب"}</button></form>}
        </>}
      </section>
    </section>
  </main>;
}
