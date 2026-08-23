import { clearLiveMarketplaceToken, getLiveAccountSnapshot, getLiveMarketplaceToken, getLiveMarketplaceUser, getSavedMarketplaceCountryId, isLiveMarketplaceEnabled, logoutLiveMarketplace, type LiveAccountSnapshot, type LiveMarketplaceUser } from "@/lib/marketplaceApi";
import { ArrowLeft, Bell, Gavel, Heart, LogOut, PackageCheck, PlusCircle, RefreshCcw, WalletCards } from "lucide-react";
import { BrandLogo } from "@/components/BrandLogo";
import React, { useEffect, useState } from "react";
import { toast } from "sonner";
import { Link, useLocation } from "wouter";

const quickActions = [
  { icon: Gavel, title: "استكشف المزادات", copy: "شاهد المزادات الحية وابدأ المزايدة.", href: "/#auctions" },
  { icon: PlusCircle, title: "اعرض مقتناك", copy: "أضف منتجك ثم أرسله للمراجعة.", href: "/sell" },
  { icon: PackageCheck, title: "طلباتي", copy: "راجع الطلبات الناتجة عن مزاداتك.", href: "#orders" },
  { icon: WalletCards, title: "محفظتي", copy: "اطّلع على الأرصدة الحية عند توفرها.", href: "#wallet" },
] as const;

export default function Account() {
  const [, navigate] = useLocation();
  const [liveSessionUser, setLiveSessionUser] = useState<LiveMarketplaceUser | null>(null);
  const [snapshot, setSnapshot] = useState<LiveAccountSnapshot | null>(null);
  const [isCheckingSession, setIsCheckingSession] = useState(false);
  const [isLoadingSnapshot, setIsLoadingSnapshot] = useState(false);
  const [liveError, setLiveError] = useState(false);
  const countryId = getSavedMarketplaceCountryId();
  const isLive = isLiveMarketplaceEnabled() && countryId > 0;

  useEffect(() => {
    if (!isLive || !getLiveMarketplaceToken()) {
      setLiveSessionUser(null);
      setSnapshot(null);
      setIsCheckingSession(false);
      return;
    }

    let cancelled = false;
    setIsCheckingSession(true);
    getLiveMarketplaceUser(countryId).then((user) => {
      if (!cancelled) setLiveSessionUser(user);
    }).catch(() => {
      clearLiveMarketplaceToken();
      if (!cancelled) setLiveSessionUser(null);
    }).finally(() => {
      if (!cancelled) setIsCheckingSession(false);
    });

    return () => { cancelled = true; };
  }, [countryId, isLive]);

  useEffect(() => {
    if (!liveSessionUser || !isLive) {
      setSnapshot(null);
      return;
    }

    let cancelled = false;
    setIsLoadingSnapshot(true);
    getLiveAccountSnapshot(countryId).then((data) => {
      if (!cancelled) {
        setSnapshot(data);
        setLiveError(false);
      }
    }).catch(() => {
      if (!cancelled) {
        setSnapshot(null);
        setLiveError(true);
      }
    }).finally(() => {
      if (!cancelled) setIsLoadingSnapshot(false);
    });

    return () => { cancelled = true; };
  }, [countryId, isLive, liveSessionUser]);

  const logout = async () => {
    try {
      if (isLive) await logoutLiveMarketplace(countryId);
    } catch {
      // The local token is still removed to prevent a stale browser session.
    }
    clearLiveMarketplaceToken();
    setLiveSessionUser(null);
    setSnapshot(null);
    toast.success("تم تسجيل الخروج من حسابك.");
    navigate("/auth");
  };

  const reloadSnapshot = () => window.location.reload();
  const accountIsReady = Boolean(liveSessionUser && !liveError);

  return <main dir="rtl" className="min-h-screen bg-[#f7f6f1] text-[#143039]">
    <header className="market-container flex h-20 items-center justify-between gap-4"><Link href="/" className="rounded-xl px-1 py-2" aria-label="Biddfy.ai"><BrandLogo /></Link><div className="flex items-center gap-3"><span className="text-sm text-[#63767a]">{liveSessionUser ? `مرحباً، ${liveSessionUser.name}` : "حساب Biddfy.ai"}</span>{liveSessionUser && <button type="button" onClick={logout} className="inline-flex items-center gap-2 rounded-xl border border-[#143039]/15 px-3 py-2 text-xs font-bold"><LogOut size={15} />تسجيل الخروج</button>}</div></header>
    <section className="market-container py-8">
      <div className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs font-bold tracking-[.14em] text-[#d96d46]">مساحة الحساب</p><h1 className="mt-2 font-serif text-4xl">كل نشاطك في مكان واحد</h1></div><span className={`rounded-full px-3 py-2 text-xs font-bold ${accountIsReady ? "bg-[#edf0e8] text-[#4e6967]" : "bg-[#fff0e8] text-[#c45e39]"}`}>{accountIsReady ? "حسابك نشط" : "تسجيل الدخول مطلوب"}</span></div>

      {isCheckingSession ? <div className="mt-6 rounded-2xl border border-[#143039]/10 bg-white p-5 text-sm text-[#63767a]">جارٍ التحقق من جلسة حسابك…</div> : !liveSessionUser ? <div className="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#d96d46]/25 bg-[#fff5f0] p-5 text-sm leading-6"><span>سجّل الدخول للوصول إلى طلباتك ومحفظتك وإشعاراتك الحية.</span><Link href="/auth" className="rounded-xl bg-[#12313a] px-4 py-2 text-xs font-bold text-white">دخول أو إنشاء حساب</Link></div> : <>
        {liveError && <div className="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#d96d46]/25 bg-[#fff5f0] p-5 text-sm leading-6 text-[#9d4027]"><span>تعذر تحميل تفاصيل حسابك الحية. لا نعرض قيماً بديلة.</span><button type="button" onClick={reloadSnapshot} className="inline-flex items-center gap-2 rounded-xl border border-[#d96d46]/30 px-4 py-2 text-xs font-bold"><RefreshCcw size={14} />إعادة المحاولة</button></div>}
        <div className="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{quickActions.map(({ icon: Icon, title, copy, href }) => <Link href={href} key={title} className="group rounded-2xl bg-white p-6 text-right shadow-[0_8px_30px_rgba(20,48,57,.07)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_34px_rgba(20,48,57,.11)]"><Icon className="text-[#d96d46]" size={24} /><h2 className="mt-8 flex items-center justify-between font-serif text-xl">{title}<ArrowLeft className="text-[#a4b0b1]" size={18} /></h2><p className="mt-3 text-sm leading-6 text-[#6b7d81]">{copy}</p></Link>)}</div>

        <div className="mt-8 grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
          <section id="orders" className="rounded-[1.5rem] bg-white p-7 shadow-[0_8px_30px_rgba(20,48,57,.07)]"><div className="flex items-center justify-between"><div><p className="text-xs font-bold text-[#d96d46]">طلبات حية</p><h2 className="mt-1 font-serif text-2xl">الحالة بعد الفوز</h2></div><PackageCheck className="text-[#d96d46]" /></div><div className="mt-6 space-y-3">{isLoadingSnapshot ? <p className="rounded-xl bg-[#f4f3ed] p-4 text-sm text-[#66777b]">جارٍ تحميل الطلبات…</p> : snapshot?.orders.length ? snapshot.orders.map((order) => <article key={order.reference} className="rounded-2xl bg-[#f1f2ec] p-4"><div className="flex flex-wrap items-start justify-between gap-3"><div><p className="text-xs text-[#718187]">{order.reference}</p><h3 className="mt-1 font-semibold">{order.title}</h3></div><span className={`rounded-full px-3 py-1 text-xs font-bold ${order.tone === "teal" ? "bg-[#dcebe5] text-[#315c54]" : "bg-[#fff0d6] text-[#a46a24]"}`}>{order.status}</span></div><p className="mt-3 text-xs text-[#718187]">{order.progress}</p></article>) : <p className="rounded-xl bg-[#f4f3ed] p-4 text-sm leading-6 text-[#66777b]">لا توجد طلبات حية لهذا الحساب حتى الآن.</p>}</div></section>
          <section id="wallet" className="rounded-[1.5rem] bg-[#12313a] p-7 text-white shadow-[0_8px_30px_rgba(20,48,57,.12)]"><p className="text-xs font-bold text-[#c7dcae]">محفظتك الحية</p><h2 className="mt-2 font-serif text-2xl">ملخص الرصيد</h2>{isLoadingSnapshot ? <p className="mt-8 text-sm text-[#cfe0dc]">جارٍ تحميل الرصيد…</p> : snapshot?.wallet ? <><strong className="mt-8 block font-serif text-4xl">{snapshot.wallet.available}</strong><p className="mt-2 text-sm text-[#cfe0dc]">متاح للعرض</p><div className="mt-6 border-t border-white/10 pt-5 text-sm text-[#cfe0dc]"><span>معلّق: </span><strong>{snapshot.wallet.pending}</strong></div></> : <p className="mt-8 rounded-xl bg-white/8 p-4 text-sm leading-6 text-[#cfe0dc]">لا يوجد رصيد مسجل لهذا الحساب حالياً.</p>}<Link href="/sell" className="mt-7 inline-flex items-center gap-2 text-sm font-bold text-[#c7dcae]">اعرض مقتناك <ArrowLeft size={15} /></Link></section>
        </div>

        <section id="notifications" className="mt-6 rounded-[1.5rem] border border-[#143039]/10 bg-white p-7"><div className="flex items-center justify-between"><div><p className="text-xs font-bold text-[#d96d46]">تنبيهات حية</p><h2 className="mt-1 font-serif text-2xl">آخر التحديثات</h2></div><Bell className="text-[#d96d46]" /></div><div className="mt-5 grid gap-3 md:grid-cols-2">{isLoadingSnapshot ? <p className="rounded-2xl bg-[#f4f3ed] p-4 text-sm text-[#66777b]">جارٍ تحميل الإشعارات…</p> : snapshot?.notifications.length ? snapshot.notifications.map((notice) => <div key={`${notice.title}-${notice.time}`} className="rounded-2xl bg-[#f4f3ed] p-4"><div className="flex justify-between gap-4"><h3 className="font-semibold">{notice.title}</h3><span className="whitespace-nowrap text-xs text-[#718187]">{notice.time}</span></div><p className="mt-2 text-sm leading-6 text-[#6b7d81]">{notice.detail}</p></div>) : <div className="rounded-2xl bg-[#f4f3ed] p-4 text-sm leading-6 text-[#66777b]">لا توجد إشعارات حية لهذا الحساب حتى الآن.</div>}</div></section>
      </>}
    </section>
  </main>;
}
