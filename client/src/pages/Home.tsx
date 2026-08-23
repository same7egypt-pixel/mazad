import { demoAuctionLots, demoCountryOptions, getDemoCountry, type DemoAuction } from "@/data/marketplaceDemo";
import { clearLiveMarketplaceToken, getLiveAuctions, getLiveMarketplaceCountries, getLiveMarketplaceToken, getLiveMarketplaceUser, getSavedMarketplaceCountryId, isLiveMarketplaceEnabled, logoutLiveMarketplace, saveMarketplaceCountryId, type LiveMarketplaceUser, type MarketplaceCountry } from "@/lib/marketplaceApi";
import { ArrowLeft, Bell, Clock3, Landmark, Menu, Search, ShieldCheck, Sparkles } from "lucide-react";
import React, { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Link, useLocation } from "wouter";

const categories = ["الكل", "فن واقتناء", "ساعات ومجوهرات", "تصوير وكاميرات", "تصميم وديكور"];

function LotImage({ lot, hero = false }: { lot: DemoAuction; hero?: boolean }) {
  return <div className={`relative overflow-hidden bg-gradient-to-br ${lot.paint} ${hero ? "h-[25rem] md:h-[31rem]" : "h-52"}`}>
    <div className="absolute inset-0 opacity-70 [background-image:radial-gradient(circle_at_74%_20%,rgba(255,255,255,.35),transparent_25%),linear-gradient(140deg,rgba(255,255,255,.16),transparent_45%)]" />
    <div className="absolute -bottom-20 -right-16 h-64 w-64 rounded-full border border-white/25 bg-white/10" />
    <span className="absolute left-7 top-5 font-serif text-[7rem] leading-none text-white/85 drop-shadow-2xl" aria-hidden>{lot.mark}</span>
    <span className="absolute left-5 top-5 rounded-full bg-[#0d232a]/65 px-3 py-1 text-[10px] font-bold tracking-[.12em] text-white backdrop-blur">مزاد حي</span>
    <span className="absolute bottom-5 right-5 flex items-center gap-2 text-xs font-semibold text-white"><Clock3 size={14} /> ينتهي خلال {lot.time}</span>
  </div>;
}

export default function Home() {
  const [, navigate] = useLocation();
  const [country, setCountry] = useState("sa");
  const [query, setQuery] = useState("");
  const [activeCategory, setActiveCategory] = useState("الكل");
  const [liveCountries, setLiveCountries] = useState<MarketplaceCountry[]>([]);
  const [lots, setLots] = useState<DemoAuction[]>(isLiveMarketplaceEnabled() ? [] : demoAuctionLots);
  const [liveError, setLiveError] = useState(false);
  const [liveAuctionsLoading, setLiveAuctionsLoading] = useState(isLiveMarketplaceEnabled());
  const [sessionUser, setSessionUser] = useState<LiveMarketplaceUser | null>(null);
  const isLive = isLiveMarketplaceEnabled();
  const countryOptions = useMemo(() => {
    if (liveCountries.length) return liveCountries.map((option) => ({ apiId: option.id, code: option.code.toLowerCase(), name: option.name, currency: option.currency?.symbol || option.currency?.code || "" }));
    return isLive ? [] : demoCountryOptions;
  }, [isLive, liveCountries]);
  const activeCountry = countryOptions.find((option) => option.code === country) || (isLive ? null : getDemoCountry(country));
  const heroLot = lots[0] || null;
  const filteredLots = lots.filter((lot) => (activeCategory === "الكل" || lot.category === activeCategory) && `${lot.title} ${lot.category} ${lot.city}`.includes(query.trim()));

  useEffect(() => {
    if (!isLive) return;

    let cancelled = false;
    getLiveMarketplaceCountries().then((countries) => {
      if (cancelled) return;
      setLiveCountries(countries);
      const firstCountry = countries[0];
      if (firstCountry && !countries.some((item) => item.code.toLowerCase() === country)) setCountry(firstCountry.code.toLowerCase());
    }).catch(() => {
      if (!cancelled) {
        setLiveError(true);
        setLots([]);
        setLiveAuctionsLoading(false);
      }
    });

    return () => { cancelled = true; };
  }, [country, isLive]);

  useEffect(() => {
    if (!isLive || !liveCountries.length) return;

    const selectedCountry = liveCountries.find((item) => item.code.toLowerCase() === country);
    if (!selectedCountry) return;
    let cancelled = false;
    setLiveAuctionsLoading(true);

    getLiveAuctions(selectedCountry).then((liveLots) => {
      if (!cancelled) {
        setLots(liveLots);
        setLiveError(false);
        setLiveAuctionsLoading(false);
      }
    }).catch(() => {
      if (!cancelled) {
        setLiveError(true);
        setLots([]);
        setLiveAuctionsLoading(false);
      }
    });

    return () => { cancelled = true; };
  }, [country, isLive, liveCountries]);

  useEffect(() => {
    if (activeCountry?.apiId) saveMarketplaceCountryId(activeCountry.apiId);
  }, [activeCountry?.apiId]);

  useEffect(() => {
    if (!isLive || !getLiveMarketplaceToken()) {
      setSessionUser(null);
      return;
    }

    const sessionCountryId = activeCountry?.apiId || getSavedMarketplaceCountryId();
    if (!sessionCountryId) return;
    let cancelled = false;
    getLiveMarketplaceUser(sessionCountryId).then((user) => {
      if (!cancelled) setSessionUser(user);
    }).catch(() => {
      clearLiveMarketplaceToken();
      if (!cancelled) setSessionUser(null);
    });

    return () => { cancelled = true; };
  }, [activeCountry?.apiId, isLive]);

  const reloadLiveData = () => window.location.reload();
  const logout = async () => {
    const sessionCountryId = activeCountry?.apiId || getSavedMarketplaceCountryId();
    try {
      if (sessionCountryId) await logoutLiveMarketplace(sessionCountryId);
    } catch {
      // Local revocation still prevents stale UI state if the network is unavailable.
    }
    clearLiveMarketplaceToken();
    setSessionUser(null);
    toast.success("تم تسجيل الخروج من حسابك.");
  };
  const marketLabel = activeCountry?.name || (liveError ? "اتصال السوق غير متاح" : "جارٍ تحميل السوق");

  return <main dir="rtl" className="min-h-screen overflow-x-hidden bg-[#f7f6f1] text-[#143039]">
    <div className="border-b border-white/10 bg-[#12313a] text-[#dce9e3]">
      <div className="market-container flex h-10 items-center justify-between text-xs">
        <span className="flex items-center gap-2"><ShieldCheck size={14} className="text-[#c7dcae]" /> {isLive ? "مزادات حية مقيدة بسياق الدولة" : "بيانات عرض مؤقتة حتى اتصال Laravel"}</span>
        <label className="flex items-center gap-2"><Landmark size={14} /><span className="sr-only">اختر الدولة</span>
          <select value={countryOptions.length ? country : ""} onChange={(event) => setCountry(event.target.value)} disabled={isLive && !countryOptions.length} className="max-w-44 bg-transparent text-xs font-semibold outline-none disabled:opacity-60">
            {countryOptions.length ? countryOptions.map((option) => <option key={option.code} value={option.code}>{option.name}</option>) : <option value="">{liveError ? "تعذر تحميل الأسواق" : "جارٍ تحميل الأسواق"}</option>}
          </select>
        </label>
      </div>
    </div>

    <header className="market-container flex h-20 items-center justify-between gap-5">
      <Link href="/" className="flex items-center gap-3"><span className="grid h-10 w-10 place-items-center rounded-2xl bg-[#d96d46] text-xl font-black text-white shadow-lg">م</span><span className="font-serif text-2xl font-semibold">مِزَاد</span></Link>
      <nav className="hidden gap-7 text-sm font-semibold text-[#5b6d72] lg:flex"><a href="#auctions" className="text-[#12313a]">المزادات</a><a href="#discover">الاكتشاف</a><a href="#how">كيف يعمل</a><Link href="/sell">اعرض مقتناك</Link></nav>
      <div className="flex items-center gap-2"><button onClick={() => toast.message("الإشعارات", { description: "سيتم ربط صندوق الإشعارات الحي في خطوة التكامل التالية." })} className="hidden h-10 w-10 place-items-center rounded-full border border-[#143039]/10 sm:grid" aria-label="إشعارات"><Bell size={18} /></button>{sessionUser ? <><Link href="/account" className="rounded-full bg-[#12313a] px-5 py-2.5 text-sm font-bold text-white">{sessionUser.name || "حسابي"}</Link><button type="button" onClick={logout} className="rounded-full border border-[#143039]/15 px-3 py-2 text-xs font-bold">خروج</button></> : <Link href="/auth" className="rounded-full bg-[#12313a] px-5 py-2.5 text-sm font-bold text-white">دخول أو إنشاء حساب</Link>}<button onClick={() => toast.message("القائمة", { description: "التنقل الكامل متاح من روابط الصفحة في هذه النسخة الأولية." })} className="grid h-10 w-10 place-items-center rounded-full border border-[#143039]/10 lg:hidden" aria-label="القائمة"><Menu size={18} /></button></div>
    </header>

    <section className="market-container pb-16 pt-5 md:pb-24 md:pt-10">
      {liveError && <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#d96d46]/30 bg-[#fff0e8] px-4 py-3 text-sm text-[#9d4027]"><span>تعذر الاتصال بالمزادات الحية حالياً. لا نعرض أي بيانات بديلة في هذه الواجهة العامة.</span><button type="button" onClick={reloadLiveData} className="rounded-lg border border-[#d96d46]/30 px-3 py-1.5 text-xs font-bold">إعادة المحاولة</button></div>}
      <div className="grid overflow-hidden rounded-[2rem] bg-[#12313a] shadow-[0_30px_90px_rgba(18,49,58,.2)] lg:grid-cols-[1.05fr_.95fr]">
        <div className="relative min-h-[31rem] p-8 text-[#fcfbf6] md:p-12"><div className="absolute inset-0 opacity-60 [background-image:radial-gradient(circle_at_12%_16%,rgba(199,220,174,.25),transparent_18%),radial-gradient(circle_at_78%_78%,rgba(217,109,70,.32),transparent_28%)]" /><div className="relative flex h-full flex-col items-start justify-between"><div><span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold text-[#c7dcae]"><Sparkles size={14} /> {marketLabel}</span><h1 className="mt-7 font-serif text-5xl leading-[1.04] md:text-7xl">كل قطعة<br />لها قصتها.</h1><p className="mt-6 max-w-md text-base leading-8 text-[#cfe0dc]">منصة مزادات منتقاة تجمع القطع الاستثنائية مع مزايدة واضحة، ومدفوعات منظمة، وتسليم قابل للتتبع.</p></div><div className="mt-12 flex flex-wrap gap-3"><a href="#auctions" className="rounded-full bg-[#d96d46] px-6 py-3 text-sm font-bold text-white">استكشف المزادات</a><a href="#how" className="rounded-full border border-white/20 px-6 py-3 text-sm font-bold">كيف يعمل مزاد؟</a></div></div></div>
        <div className="relative grid min-h-[26rem] place-items-center bg-[#d9cab7] p-5 md:p-8">{isLive && liveError ? <div className="rounded-2xl bg-[#fcfbf6]/95 p-8 text-center shadow-xl"><p className="font-serif text-2xl font-semibold">المزادات الحية غير متاحة مؤقتاً</p><p className="mt-2 text-sm text-[#61757a]">تحقق من الاتصال ثم أعد المحاولة.</p></div> : isLive && liveAuctionsLoading ? <div className="rounded-2xl bg-[#fcfbf6]/95 p-8 text-center shadow-xl"><p className="font-serif text-2xl font-semibold">جارٍ تحميل المزادات الحية</p><p className="mt-2 text-sm text-[#61757a]">قد يستغرق إيقاظ الخادم التجريبي بضع ثوانٍ.</p></div> : isLive && !lots.length ? <div className="rounded-2xl bg-[#fcfbf6]/95 p-8 text-center shadow-xl"><p className="font-serif text-2xl font-semibold">لا توجد مزادات حية حالياً</p><p className="mt-2 text-sm text-[#61757a]">أضف منتجاً معتمداً وجدول مزاداً ليظهر هنا.</p></div> : heroLot ? <><LotImage lot={heroLot} hero /><div className="absolute bottom-10 left-10 right-10 rounded-2xl bg-[#fcfbf6]/95 p-5 shadow-xl backdrop-blur"><p className="text-xs font-bold text-[#d96d46]">{heroLot.category}</p><div className="mt-2 flex items-end justify-between gap-3"><div><h2 className="font-serif text-2xl font-semibold">{heroLot.title}</h2><p className="mt-1 text-xs text-[#61757a]">{heroLot.city} · مزاد مباشر</p></div><strong className="shrink-0">{heroLot.price}</strong></div></div></> : null}</div>
      </div>
    </section>

    <section id="discover" className="border-y border-[#143039]/10 bg-[#ebe7dc] py-8"><div className="market-container grid gap-4 lg:grid-cols-[1fr_auto]"><label className="flex items-center gap-3 rounded-2xl bg-[#f8f7f2] px-4 py-3 shadow-sm"><Search className="shrink-0 text-[#d96d46]" size={20} /><input value={query} onChange={(event) => setQuery(event.target.value)} aria-label="ابحث في المزادات" placeholder="ابحث عن قطعة، فئة أو مدينة" className="w-full bg-transparent text-sm outline-none placeholder:text-[#879497]" /></label><div className="flex gap-2 overflow-x-auto pb-1 lg:max-w-[39rem]">{categories.map((name) => <button key={name} onClick={() => setActiveCategory(name)} className={`shrink-0 rounded-xl border px-4 py-3 text-sm font-semibold transition ${activeCategory === name ? "border-[#12313a] bg-[#12313a] text-white" : "border-[#143039]/10 bg-[#f8f7f2] hover:border-[#d96d46]"}`}>{name}</button>)}</div></div></section>
    <section id="auctions" className="market-container py-16 md:py-24"><div className="mb-9 flex items-end justify-between"><div><p className="text-xs font-bold tracking-[.16em] text-[#d96d46]">مزادات حية الآن</p><h2 className="mt-2 font-serif text-4xl md:text-5xl">فرص لا تتكرر</h2></div><span className="hidden text-sm text-[#6f8084] sm:block">{filteredLots.length} مزادات مطابقة</span></div>{filteredLots.length ? <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">{filteredLots.map((lot) => <article key={lot.id} className="group overflow-hidden rounded-[1.35rem] bg-white shadow-[0_10px_35px_rgba(20,48,57,.08)] transition hover:-translate-y-1 hover:shadow-[0_18px_45px_rgba(20,48,57,.15)]"><Link href={`/auction/${lot.id}?countryId=${activeCountry?.apiId ?? 0}`}><LotImage lot={lot} /></Link><div className="p-5"><p className="text-xs font-bold text-[#d96d46]">{lot.category}</p><Link href={`/auction/${lot.id}?countryId=${activeCountry?.apiId ?? 0}`}><h3 className="mt-2 font-serif text-xl font-semibold transition group-hover:text-[#d96d46]">{lot.title}</h3></Link><p className="mt-2 text-xs text-[#718187]">{lot.city} · مزاد مباشر</p><div className="mt-5 flex items-end justify-between border-t border-[#143039]/8 pt-4"><span className="text-xs text-[#718187]">المزايدة الحالية</span><strong>{lot.price}</strong></div></div></article>)}</div> : <div className="rounded-3xl border border-dashed border-[#143039]/20 p-10 text-center text-[#6f8084]">{isLive && liveError ? "تعذر الاتصال بالمزادات الحية. لا توجد بيانات بديلة معروضة." : isLive && !liveAuctionsLoading ? "لا توجد مزادات حية في السوق المحدد حالياً." : "لم نجد مزاداً مطابقاً. جرّب فئة أخرى أو كلمات بحث مختلفة."}</div>}</section>
    <section id="how" className="bg-[#e6e2d6] py-16 md:py-24"><div className="market-container grid gap-10 lg:grid-cols-[.8fr_1.2fr]"><div><p className="text-xs font-bold tracking-[.16em] text-[#d96d46]">مزاد يضع الوضوح أولاً</p><h2 className="mt-3 font-serif text-4xl leading-tight md:text-5xl">تجربة مدروسة<br />من أول مزايدة.</h2></div><div className="grid gap-px overflow-hidden rounded-3xl bg-[#143039]/10 sm:grid-cols-3">{[["01", "تحقق من القطعة", "البيانات والوسائط وسياق البائع في مكان واحد."], ["02", "زايد بثقة", "سعر حي وحد أدنى واضح وحماية ذرية."], ["03", "أكمل الاستلام", "طلب منظم وشحن قابل للمتابعة."]].map(([num, title, copy]) => <div key={num} className="bg-[#f7f6f1] p-6"><span className="font-serif text-3xl text-[#d96d46]">{num}</span><h3 className="mt-8 font-serif text-xl font-semibold">{title}</h3><p className="mt-3 text-sm leading-6 text-[#65767b]">{copy}</p></div>)}</div></div></section>
    <section id="sell" className="market-container py-16 md:py-24"><div className="flex flex-col items-start justify-between gap-8 rounded-[2rem] bg-[#d96d46] p-8 text-white md:flex-row md:items-end md:p-12"><div><p className="text-xs font-bold tracking-[.16em] text-[#ffe3cc]">هل لديك قطعة استثنائية؟</p><h2 className="mt-3 max-w-2xl font-serif text-4xl leading-tight md:text-5xl">ابدأ مسار بيع واضحاً، من المسودة إلى المزاد.</h2></div><button onClick={() => navigate("/sell")} className="flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-[#b94f31]">اعرض مقتناك <ArrowLeft size={16} /></button></div></section>
    <footer className="border-t border-[#143039]/10 py-8"><div className="market-container flex flex-col justify-between gap-4 text-xs text-[#718187] sm:flex-row"><span>© 2026 مِزَاد · {isLive ? "واجهة مرتبطة ببيانات Laravel الحية" : "واجهة عرض مؤقتة لحين ربط بيانات Laravel الحية"}</span><div className="flex gap-5"><a href="#how">عن المنصة</a><a href="#auctions">المزادات</a><a href="#sell">البائعون</a></div></div></footer>
  </main>;
}
