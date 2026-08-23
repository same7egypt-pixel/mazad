import { ArrowRight, SearchX } from "lucide-react";
import { Link } from "wouter";

export default function NotFound() {
  return (
    <main dir="rtl" className="grid min-h-screen place-items-center bg-[#f7f6f1] p-6 text-[#143039]">
      <section className="relative w-full max-w-2xl overflow-hidden rounded-[2rem] bg-[#12313a] px-7 py-14 text-center text-white shadow-[0_30px_90px_rgba(18,49,58,.25)] sm:px-14">
        <div className="absolute -left-24 -top-24 h-64 w-64 rounded-full bg-[#d96d46]/35 blur-3xl" />
        <div className="absolute -bottom-24 -right-24 h-64 w-64 rounded-full bg-[#c7dcae]/20 blur-3xl" />
        <div className="relative"><span className="font-serif text-2xl">Biddfy.ai</span><div className="mx-auto mt-11 grid h-16 w-16 place-items-center rounded-2xl border border-white/15 bg-white/10 text-[#c7dcae]"><SearchX size={30} /></div><p className="mt-7 text-xs font-bold tracking-[.2em] text-[#c7dcae]">404</p><h1 className="mt-3 font-serif text-4xl sm:text-5xl">لم نعثر على هذه القطعة.</h1><p className="mx-auto mt-5 max-w-md text-sm leading-7 text-[#cedfdb]">قد يكون الرابط غير صحيح أو أن المزاد لم يعد متاحاً. يمكنك الرجوع لاستكشاف المزادات الحية.</p><Link href="/" className="mx-auto mt-9 inline-flex items-center gap-2 rounded-full bg-[#d96d46] px-6 py-3 text-sm font-bold text-white transition hover:brightness-105"><ArrowRight size={17} />العودة إلى المزادات</Link></div>
      </section>
    </main>
  );
}
