import { demoBidActivity, getDemoAuction } from "@/data/marketplaceDemo";
import {
  clearLiveMarketplaceToken,
  getLiveAuction,
  getLiveBidActivity,
  getLiveMarketplaceToken,
  getLiveMarketplaceUser,
  isLiveMarketplaceEnabled,
  placeLiveBid,
  type LiveMarketplaceUser,
} from "@/lib/marketplaceApi";
import { ArrowRight, Clock3, Gavel, Heart, MapPin, PackageCheck, ShieldCheck, Truck } from "lucide-react";
import React, { useEffect, useState } from "react";
import { toast } from "sonner";
import { Link, useRoute } from "wouter";

export default function AuctionDetail() {
  const [, params] = useRoute("/auction/:id");
  const auctionId = Number(params?.id ?? 1);
  const fallbackAuction = getDemoAuction(auctionId);
  const countryId = typeof window === "undefined" ? 0 : Number(new URLSearchParams(window.location.search).get("countryId"));
  const isLive = isLiveMarketplaceEnabled() && countryId > 0;
  const [auction, setAuction] = useState(fallbackAuction);
  const [bidActivity, setBidActivity] = useState(demoBidActivity);
  const [liveError, setLiveError] = useState(false);
  const [liveSessionUser, setLiveSessionUser] = useState<LiveMarketplaceUser | null>(null);
  const [isCheckingLiveSession, setIsCheckingLiveSession] = useState(true);
  const [isBidding, setIsBidding] = useState(false);
  const [amount, setAmount] = useState("");
  const [saved, setSaved] = useState(false);
  const canPlaceLiveBid = isLive && !liveError && !!liveSessionUser;

  useEffect(() => {
    if (!isLive || !getLiveMarketplaceToken()) {
      setLiveSessionUser(null);
      setIsCheckingLiveSession(false);
      return;
    }

    let cancelled = false;
    setIsCheckingLiveSession(true);
    getLiveMarketplaceUser(countryId)
      .then((user) => {
        if (!cancelled) setLiveSessionUser(user);
      })
      .catch(() => {
        clearLiveMarketplaceToken();
        if (!cancelled) setLiveSessionUser(null);
      })
      .finally(() => {
        if (!cancelled) setIsCheckingLiveSession(false);
      });

    return () => {
      cancelled = true;
    };
  }, [countryId, isLive]);

  useEffect(() => {
    setAuction(fallbackAuction);
    setBidActivity(demoBidActivity);
    setLiveError(false);
    if (!isLive) return;

    let cancelled = false;
    Promise.all([getLiveAuction(auctionId, countryId), getLiveBidActivity(auctionId, countryId)])
      .then(([liveAuction, liveBids]) => {
        if (!cancelled) {
          setAuction(liveAuction);
          setBidActivity(liveBids);
        }
      })
      .catch(() => {
        if (!cancelled) setLiveError(true);
      });

    return () => {
      cancelled = true;
    };
  }, [auctionId, countryId, fallbackAuction, isLive]);

  const submitBid = async () => {
    if (!amount.trim()) {
      toast.message("أدخل قيمة للمزايدة أولاً.");
      return;
    }
    if (!canPlaceLiveBid) {
      toast.info("تحتاج المزايدة الحية إلى جلسة Laravel صالحة. سجّل الدخول من مسار البيع أولاً.");
      return;
    }

    setIsBidding(true);
    try {
      await placeLiveBid(countryId, auctionId, amount.trim());
      const [updatedAuction, updatedBids] = await Promise.all([
        getLiveAuction(auctionId, countryId),
        getLiveBidActivity(auctionId, countryId),
      ]);
      setAuction(updatedAuction);
      setBidActivity(updatedBids);
      setAmount("");
      toast.success("تم تسجيل المزايدة", { description: "تم تحديث السعر وسجل الحركة من Laravel." });
    } catch {
      toast.error("تعذر تسجيل المزايدة. تحقق من الجلسة، حالة المزاد، والحد الأدنى للمزايدة.");
    } finally {
      setIsBidding(false);
    }
  };

  return (
    <main dir="rtl" className="min-h-screen bg-[#f7f6f1] text-[#143039]">
      <header className="market-container flex h-20 items-center justify-between">
        <Link href="/" className="font-serif text-2xl font-semibold">مِزَاد</Link>
        <Link href="/" className="flex items-center gap-2 text-sm font-bold"><ArrowRight size={17} /> الرجوع للمزادات</Link>
      </header>

      <section className="market-container grid gap-8 pb-16 pt-6 lg:grid-cols-[1.15fr_.85fr]">
        <div className={`relative min-h-[31rem] overflow-hidden rounded-[2rem] bg-gradient-to-br ${auction.paint}`}>
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_72%_24%,rgba(255,255,255,.35),transparent_24%),linear-gradient(140deg,rgba(255,255,255,.18),transparent_46%)]" />
          <span className="absolute left-8 top-7 font-serif text-[12rem] leading-none text-white/90">{auction.mark}</span>
          <button
            type="button"
            onClick={() => {
              setSaved(!saved);
              toast.message(saved ? "أزيل من المحفوظات التجريبية" : "أضيف إلى المحفوظات التجريبية");
            }}
            className={`absolute right-6 top-6 grid h-11 w-11 place-items-center rounded-full transition ${saved ? "bg-[#d96d46] text-white" : "bg-white/90 text-[#12313a]"}`}
            aria-label="حفظ المزاد"
          >
            <Heart size={19} fill={saved ? "currentColor" : "none"} />
          </button>
          <div className="absolute bottom-6 right-6 rounded-full bg-[#12313a]/75 px-4 py-2 text-xs font-bold text-white backdrop-blur">وسائط عرض مؤقتة لحين ربط التخزين الخاص</div>
        </div>

        <aside className="rounded-[2rem] bg-white p-7 shadow-[0_15px_50px_rgba(20,48,57,.08)] md:p-9">
          <div className="flex items-center justify-between gap-3">
            <p className="text-xs font-bold text-[#d96d46]">{auction.category} · مزاد حي</p>
            <span className="rounded-full bg-[#fff0e8] px-2.5 py-1 text-[10px] font-bold text-[#c45e39]">{isLive && !liveError ? "بيانات Laravel حية" : "بيانات عرض"}</span>
          </div>
          <h1 className="mt-3 font-serif text-4xl leading-tight">{auction.title}</h1>
          <p className="mt-4 flex items-center gap-2 text-sm text-[#6d7e82]"><MapPin size={16} />{auction.city} · المملكة العربية السعودية</p>
          <div className="mt-8 rounded-2xl bg-[#edf0e8] p-5">
            <div className="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between"><span className="text-[#64777b]">المزايدة الحالية</span><strong className="font-serif text-2xl sm:text-3xl">{auction.price}</strong></div>
            <div className="mt-5 flex items-center gap-2 border-t border-[#143039]/10 pt-4 text-sm font-bold text-[#bf5a36]"><Clock3 size={17} /> ينتهي خلال {auction.time}</div>
          </div>
          <div className="mt-6 flex flex-col gap-2 sm:flex-row">
            <input value={amount} onChange={(event) => setAmount(event.target.value)} aria-label="قيمة المزايدة" placeholder="أدخل مزايدتك" className="min-w-0 flex-1 rounded-xl border border-[#143039]/15 px-4 py-3 text-sm outline-none focus:border-[#d96d46]" />
            <button type="button" onClick={submitBid} disabled={isBidding || isCheckingLiveSession} className="flex items-center justify-center gap-2 rounded-xl bg-[#12313a] px-4 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"><Gavel size={16} />{isBidding ? "جارٍ التسجيل…" : "زايد الآن"}</button>
          </div>
          <div className="mt-6 flex gap-2 rounded-xl border border-[#143039]/10 p-4 text-xs leading-5 text-[#61757a]">
            <ShieldCheck className="mt-0.5 shrink-0 text-[#5e7e69]" size={18} />
            <span>
              {canPlaceLiveBid
                ? `ستُرسل المزايدة إلى Laravel باسم ${liveSessionUser.name} وتُحدّث من الخادم عند قبولها.`
                : isCheckingLiveSession
                  ? "جارٍ التحقق من جلسة Laravel قبل إتاحة المزايدة."
                  : isLive
                    ? <>تحتاج المزايدة الحية إلى جلسة Laravel صالحة. <Link href="/sell" className="font-bold text-[#bf5a36]">دخول Laravel</Link></>
                    : "هذا الزر لا يرسل مزايدة لأن Laravel API غير مهيأ."}
            </span>
          </div>
        </aside>
      </section>

      <section className="market-container grid gap-6 pb-20 lg:grid-cols-[1.15fr_.85fr]">
        <article className="rounded-[1.5rem] bg-white p-7 shadow-[0_10px_35px_rgba(20,48,57,.06)]">
          <p className="text-xs font-bold tracking-[.14em] text-[#d96d46]">عن القطعة</p>
          <h2 className="mt-2 font-serif text-3xl">تفاصيل منظمة للعرض</h2>
          <p className="mt-4 max-w-2xl text-sm leading-7 text-[#64777b]">{auction.description}</p>
          <dl className="mt-7 grid gap-4 border-t border-[#143039]/10 pt-6 sm:grid-cols-2">
            <div><dt className="text-xs text-[#77878a]">الحالة</dt><dd className="mt-1 font-semibold">{auction.condition}</dd></div>
            <div><dt className="text-xs text-[#77878a]">البائع</dt><dd className="mt-1 font-semibold">{auction.sellerLabel}</dd></div>
            <div><dt className="text-xs text-[#77878a]">التسليم</dt><dd className="mt-1 flex items-center gap-2 font-semibold"><Truck size={15} className="text-[#d96d46]" />{auction.shippingLabel}</dd></div>
            <div><dt className="text-xs text-[#77878a]">حماية العملية</dt><dd className="mt-1 flex items-center gap-2 font-semibold"><PackageCheck size={15} className="text-[#d96d46]" />تُستبدل بالمزود الفعلي لاحقاً</dd></div>
          </dl>
        </article>
        <article className="rounded-[1.5rem] bg-[#12313a] p-7 text-white shadow-[0_10px_35px_rgba(20,48,57,.12)]">
          <div className="flex items-center justify-between"><div><p className="text-xs font-bold tracking-[.14em] text-[#c7dcae]">{isLive && !liveError ? "سجل مزايدات حي" : "سجل مزايدات تجريبي"}</p><h2 className="mt-2 font-serif text-2xl">الحركة الأخيرة</h2></div><Gavel className="text-[#d96d46]" /></div>
          <div className="mt-6 divide-y divide-white/10">{bidActivity.map((bid) => <div key={`${bid.amount}-${bid.time}`} className="flex items-center justify-between py-4"><div><strong className="text-sm">{bid.amount}</strong><p className="mt-1 text-xs text-[#bdcfca]">{bid.label}</p></div><span className="text-xs text-[#bdcfca]">{bid.time}</span></div>)}</div>
          <p className="mt-4 rounded-xl bg-white/8 p-3 text-xs leading-5 text-[#cfe0dc]">لا تُعرض هوية مزايدين حقيقيين هنا، ويستبدل هذا القسم بسجل Laravel المصرح به عند الربط.</p>
        </article>
      </section>
    </main>
  );
}
