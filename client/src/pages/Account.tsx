import { startLogin } from "@/const";
import { useAuth } from "@/_core/hooks/useAuth";
import { demoAccountSnapshot, demoAccountSummaryCards } from "@/data/marketplaceDemo";
import { getLiveAccountSnapshot, getSavedMarketplaceCountryId, isLiveMarketplaceEnabled } from "@/lib/marketplaceApi";
import { Bell, ChevronLeft, Gavel, Heart, PackageCheck, UserRound, WalletCards } from "lucide-react";
import React, { useEffect, useState } from "react";
import { Link } from "wouter";

const modules = [[Gavel, "مزايداتي", "تابع المزايدات والمزادات المحفوظة"], [PackageCheck, "طلباتي", "الدفع والشحن والاستلام"], [WalletCards, "محفظتي", "الأرباح والسحوبات وحركة الرصيد"], [Bell, "الإشعارات", "تحديثات المزادات والطلبات"]] as const;
const summaryIcons = [Heart, Gavel] as const;

type AccountSnapshotView = {
  wallet: { available: string; pending: string; label: string };
  orders: Array<{ reference: string; title: string; status: string; progress: string; tone: "amber" | "teal" }>;
  notifications: Array<{ title: string; detail: string; time: string }>;
};

const fallbackAccountSnapshot: AccountSnapshotView = {
  wallet: { ...demoAccountSnapshot.wallet },
  orders: demoAccountSnapshot.orders.map((order) => ({ ...order })),
  notifications: demoAccountSnapshot.notifications.map((notice) => ({ ...notice })),
};

export default function Account() {
  const { user, isAuthenticated, loading } = useAuth();
  const [accountSnapshot, setAccountSnapshot] = useState<AccountSnapshotView>(fallbackAccountSnapshot);
  const [liveError, setLiveError] = useState(false);
  const countryId = getSavedMarketplaceCountryId();
  const isLive = isLiveMarketplaceEnabled() && countryId > 0;

  useEffect(() => {
    if (!isAuthenticated || !isLive) return;

    let cancelled = false;
    getLiveAccountSnapshot(countryId).then((snapshot) => {
      if (!cancelled) {
        setAccountSnapshot({
          wallet: snapshot.wallet || fallbackAccountSnapshot.wallet,
          orders: snapshot.orders,
          notifications: snapshot.notifications,
        });
        setLiveError(false);
      }
    }).catch(() => {
      if (!cancelled) {
        setAccountSnapshot(fallbackAccountSnapshot);
        setLiveError(true);
      }
    });

    return () => { cancelled = true; };
  }, [countryId, isAuthenticated, isLive]);

  if (!isAuthenticated && !loading) {
    return <main dir="rtl" className="grid min-h-screen place-items-center bg-[#f7f6f1] p-6 text-[#143039]"><div className="max-w-md rounded-[2rem] bg-white p-9 text-center shadow-xl"><span className="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-[#d96d46] text-white"><UserRound /></span><h1 className="mt-6 font-serif text-3xl">حسابك في مِزَاد</h1><p className="mt-3 text-sm leading-6 text-[#6b7d81]">سجّل الدخول للوصول إلى مزايداتك وطلباتك ومحفظتك وإشعاراتك.</p><button onClick={startLogin} className="mt-7 w-full rounded-xl bg-[#12313a] py-3 text-sm font-bold text-white">دخول الحساب</button><Link href="/" className="mt-4 block text-sm font-bold text-[#d96d46]">العودة للرئيسية</Link></div></main>;
  }

  const dataSourceLabel = isLive && !liveError ? "بيانات Laravel حية" : "بيانات عرض مؤقتة";

  return <main dir="rtl" className="min-h-screen bg-[#f7f6f1] text-[#143039]"><header className="market-container flex h-20 items-center justify-between"><Link href="/" className="font-serif text-2xl font-semibold">مِزَاد</Link><span className="text-sm text-[#63767a]">مرحباً، {user?.name || "مستخدم مِزَاد"}</span></header><section className="market-container py-8"><div className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs font-bold tracking-[.14em] text-[#d96d46]">مساحة الحساب</p><h1 className="mt-2 font-serif text-4xl">كل نشاطك في مكان واحد</h1></div><span className="rounded-full bg-[#fff0e8] px-3 py-2 text-xs font-bold text-[#c45e39]">{dataSourceLabel}</span></div><div className="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{modules.map(([Icon, title, copy]) => <div key={title} className="group rounded-2xl bg-white p-6 text-right shadow-[0_8px_30px_rgba(20,48,57,.07)]"><Icon className="text-[#d96d46]" size={24} /><h2 className="mt-8 flex items-center justify-between font-serif text-xl">{title}<ChevronLeft className="text-[#a4b0b1]" size={18} /></h2><p className="mt-3 text-sm leading-6 text-[#6b7d81]">{copy}</p></div>)}</div><section aria-label="مؤشرات الحساب التجريبية" className="mt-6 grid gap-4 sm:grid-cols-2">{demoAccountSummaryCards.map((summary, index) => { const Icon = summaryIcons[index]; return <article key={summary.key} className="rounded-[1.35rem] border border-[#143039]/10 bg-white p-6 shadow-[0_8px_30px_rgba(20,48,57,.05)]"><div className="flex items-center justify-between"><span className="grid h-10 w-10 place-items-center rounded-xl bg-[#fff0e8] text-[#d96d46]"><Icon size={19} /></span><span className="text-xs font-bold text-[#c45e39]">{dataSourceLabel}</span></div><strong className="mt-6 block font-serif text-4xl">{summary.value}</strong><h2 className="mt-1 font-serif text-xl">{summary.label}</h2><p className="mt-2 text-sm text-[#6b7d81]">{summary.description}</p></article>; })}</section><div className="mt-8 grid gap-6 lg:grid-cols-[1.15fr_.85fr]"><section className="rounded-[1.5rem] bg-white p-7 shadow-[0_8px_30px_rgba(20,48,57,.07)]"><div className="flex items-center justify-between"><div><p className="text-xs font-bold text-[#d96d46]">{isLive && !liveError ? "طلبات حية" : "طلبات عرض"}</p><h2 className="mt-1 font-serif text-2xl">الحالة بعد الفوز</h2></div><PackageCheck className="text-[#d96d46]" /></div><div className="mt-6 space-y-3">{accountSnapshot.orders.map((order) => <article key={order.reference} className="rounded-2xl bg-[#f1f2ec] p-4"><div className="flex flex-wrap items-start justify-between gap-3"><div><p className="text-xs text-[#718187]">{order.reference}</p><h3 className="mt-1 font-semibold">{order.title}</h3></div><span className={`rounded-full px-3 py-1 text-xs font-bold ${order.tone === "teal" ? "bg-[#dcebe5] text-[#315c54]" : "bg-[#fff0d6] text-[#a46a24]"}`}>{order.status}</span></div><p className="mt-3 text-xs text-[#718187]">{order.progress}{isLive && !liveError ? "" : " · عرض واجهة مؤقت"}</p></article>)}</div></section><section className="rounded-[1.5rem] bg-[#12313a] p-7 text-white shadow-[0_8px_30px_rgba(20,48,57,.12)]"><p className="text-xs font-bold text-[#c7dcae]">{accountSnapshot.wallet.label}</p><h2 className="mt-2 font-serif text-2xl">ملخص الرصيد</h2><strong className="mt-8 block font-serif text-4xl">{accountSnapshot.wallet.available}</strong><p className="mt-2 text-sm text-[#cfe0dc]">متاح للعرض</p><div className="mt-6 border-t border-white/10 pt-5 text-sm text-[#cfe0dc]"><span>معلّق: </span><strong>{accountSnapshot.wallet.pending}</strong></div><div className="mt-6 rounded-xl bg-white/8 p-3 text-xs leading-5 text-[#cfe0dc]">{isLive && !liveError ? "الأرصدة معروضة من دفتر المحفظة الحي." : "يستبدل رصيد العرض بدفاتر المحافظ والسحوبات الفعلية عند الربط."}</div></section></div><section className="mt-6 rounded-[1.5rem] border border-[#143039]/10 bg-white p-7"><div className="flex items-center justify-between"><div><p className="text-xs font-bold text-[#d96d46]">{isLive && !liveError ? "تنبيهات حية" : "تنبيهات العرض"}</p><h2 className="mt-1 font-serif text-2xl">آخر التحديثات</h2></div><Bell className="text-[#d96d46]" /></div><div className="mt-5 grid gap-3 md:grid-cols-2">{accountSnapshot.notifications.map((notice) => <div key={`${notice.title}-${notice.time}`} className="rounded-2xl bg-[#f4f3ed] p-4"><div className="flex justify-between gap-4"><h3 className="font-semibold">{notice.title}</h3><span className="whitespace-nowrap text-xs text-[#718187]">{notice.time}</span></div><p className="mt-2 text-sm leading-6 text-[#6b7d81]">{notice.detail}</p></div>)}</div></section></section></main>;
}
