import { startLogin } from "@/const";
import { useAuth } from "@/_core/hooks/useAuth";
import { demoCountryOptions } from "@/data/marketplaceDemo";
import { ArrowRight, CalendarClock, Check, ChevronLeft, FileImage, Gavel, ImagePlus, MapPin, ShieldCheck, Video } from "lucide-react";
import React, { FormEvent, useMemo, useState } from "react";
import { toast } from "sonner";
import { Link } from "wouter";

const citiesByCountry: Record<string, string[]> = {
  sa: ["الرياض", "جدة", "الدمام", "المدينة"],
  ae: ["دبي", "أبوظبي", "الشارقة"],
  eg: ["القاهرة", "الإسكندرية", "الجيزة"],
};

const categories = ["ساعات ومجوهرات", "فن واقتناء", "تصوير وكاميرات", "تصميم وديكور", "سيارات", "تقنية"];

export default function SellSetup() {
  const { isAuthenticated, loading } = useAuth();
  const [step, setStep] = useState<1 | 2>(1);
  const [country, setCountry] = useState("sa");
  const [city, setCity] = useState("الرياض");
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [category, setCategory] = useState(categories[0]);
  const [condition, setCondition] = useState("مستعملة بحالة ممتازة");
  const [startingPrice, setStartingPrice] = useState("");
  const [reservePrice, setReservePrice] = useState("");
  const [increment, setIncrement] = useState("100");
  const [startTime, setStartTime] = useState("");
  const [endTime, setEndTime] = useState("");
  const cities = useMemo(() => citiesByCountry[country] ?? [], [country]);

  const changeCountry = (nextCountry: string) => {
    setCountry(nextCountry);
    setCity(citiesByCountry[nextCountry]?.[0] ?? "");
  };

  const continueToAuction = () => {
    if (!isAuthenticated && !loading) return startLogin();
    if (!title.trim() || !description.trim()) {
      toast.error("أكمل عنوان القطعة ووصفها أولاً.");
      return;
    }
    setStep(2);
  };

  const saveDraft = (event: FormEvent) => {
    event.preventDefault();
    if (!isAuthenticated && !loading) return startLogin();
    if (!startingPrice || !increment || !startTime || !endTime) {
      toast.error("أكمل السعر والزيادة وتوقيت البداية والنهاية.");
      return;
    }
    toast.success("تم حفظ مسودة العرض فقط", { description: "لن يُنشأ منتج أو مزاد حقيقي قبل تفعيل عنوان Laravel API وربط المصادقة." });
  };

  return <main dir="rtl" className="min-h-screen bg-[#f7f6f1] text-[#143039]"><header className="market-container flex h-20 items-center justify-between"><Link href="/" className="font-serif text-2xl font-semibold">مِزَاد</Link><Link href="/" className="flex items-center gap-2 text-sm font-bold"><ArrowRight size={17} />العودة إلى المزادات</Link></header><section className="market-container pb-20 pt-4"><div className="mx-auto max-w-5xl"><div className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs font-bold tracking-[.16em] text-[#d96d46]">مسار البائع</p><h1 className="mt-2 font-serif text-4xl md:text-5xl">ابدأ من القطعة، ثم جهّز مزادك.</h1><p className="mt-4 max-w-2xl text-sm leading-7 text-[#66777b]">هذه واجهة إعداد مؤقتة تعكس الحقول التي يحتاجها عقد Laravel. لا تحفظ الوسائط أو المنتجات أو المزادات على الخادم حتى تفعيل الربط الحي.</p></div><span className="rounded-full bg-[#fff0e8] px-3 py-2 text-xs font-bold text-[#c45e39]">عرض مؤقت غير منشور</span></div><div className="mt-9 grid gap-8 lg:grid-cols-[.72fr_1.28fr]"><aside className="rounded-[1.5rem] bg-[#12313a] p-6 text-white"><p className="text-xs font-bold tracking-[.14em] text-[#c7dcae]">رحلة الإدراج</p><ol className="mt-7 space-y-5">{[[1, "تفاصيل القطعة", "العنوان والوصف والفئة والموقع"], [2, "إعداد المزاد", "السعر والتوقيت والحدود"]].map(([number, label, copy]) => <li key={number} className="flex gap-3"><span className={`grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold ${step === Number(number) ? "bg-[#d96d46] text-white" : step > Number(number) ? "bg-[#c7dcae] text-[#12313a]" : "bg-white/10 text-[#cfe0dc]"}`}>{step > Number(number) ? <Check size={15} /> : number}</span><div><h2 className="font-serif text-xl">{label}</h2><p className="mt-1 text-xs leading-5 text-[#cfe0dc]">{copy}</p></div></li>)}</ol><div className="mt-9 rounded-2xl bg-white/8 p-4 text-xs leading-6 text-[#cfe0dc]"><ShieldCheck className="mb-2 text-[#c7dcae]" size={18} />ستنتقل الحالة الفعلية لاحقاً من مسودة إلى مراجعة واعتماد ثم مزاد حي وفق سياسات السوق والدولة.</div></aside><form onSubmit={saveDraft} className="rounded-[1.5rem] bg-white p-6 shadow-[0_12px_40px_rgba(20,48,57,.08)] md:p-8">{step === 1 ? <><div className="flex items-center justify-between"><div><p className="text-xs font-bold text-[#d96d46]">الخطوة 1 من 2</p><h2 className="mt-1 font-serif text-3xl">تفاصيل القطعة</h2></div><FileImage className="text-[#d96d46]" /></div><div className="mt-7 grid gap-5"><label className="grid gap-2 text-sm font-semibold">عنوان القطعة<input value={title} onChange={(event) => setTitle(event.target.value)} placeholder="مثال: ساعة ميكانيكية كلاسيكية" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">الوصف<textarea value={description} onChange={(event) => setDescription(event.target.value)} placeholder="صف حالة القطعة وتفاصيلها المهمة." rows={5} className="resize-none rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><div className="grid gap-5 sm:grid-cols-2"><label className="grid gap-2 text-sm font-semibold">الفئة<select value={category} onChange={(event) => setCategory(event.target.value)} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46]">{categories.map((item) => <option key={item}>{item}</option>)}</select></label><label className="grid gap-2 text-sm font-semibold">الحالة<select value={condition} onChange={(event) => setCondition(event.target.value)} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46]"><option>جديدة</option><option>مستعملة بحالة ممتازة</option><option>مستعملة بحالة جيدة</option><option>تحتاج معاينة</option></select></label></div><div className="grid gap-5 sm:grid-cols-2"><label className="grid gap-2 text-sm font-semibold"><span className="flex items-center gap-2"><MapPin size={15} />الدولة</span><select value={country} onChange={(event) => changeCountry(event.target.value)} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46]">{demoCountryOptions.map((option) => <option key={option.code} value={option.code}>{option.name}</option>)}</select></label><label className="grid gap-2 text-sm font-semibold">المدينة<select value={city} onChange={(event) => setCity(event.target.value)} className="rounded-xl border border-[#143039]/15 bg-white px-4 py-3 font-normal outline-none focus:border-[#d96d46]">{cities.map((item) => <option key={item}>{item}</option>)}</select></label></div><div className="grid gap-4 sm:grid-cols-2"><label className="flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-[#143039]/20 bg-[#f4f3ed] p-5 text-center"><ImagePlus className="text-[#d96d46]" /><span className="mt-3 text-sm font-semibold">صور القطعة</span><span className="mt-1 text-xs leading-5 text-[#6d7f83]">اختيار ملفات للعرض فقط</span><input type="file" accept="image/*" multiple className="sr-only" onChange={() => toast.message("وسائط مؤقتة", { description: "سيُفعّل الرفع الخاص إلى S3 عند اكتمال الربط." })} /></label><label className="flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-[#143039]/20 bg-[#f4f3ed] p-5 text-center"><Video className="text-[#d96d46]" /><span className="mt-3 text-sm font-semibold">فيديو اختياري</span><span className="mt-1 text-xs leading-5 text-[#6d7f83]">لا يُرفع إلى الخادم في هذه النسخة</span><input type="file" accept="video/*" className="sr-only" onChange={() => toast.message("فيديو مؤقت", { description: "سيُربط بسعة التخزين والسياسات عند تفعيل الخدمة الحية." })} /></label></div></div><button type="button" onClick={continueToAuction} className="mt-8 flex w-full items-center justify-center gap-2 rounded-xl bg-[#12313a] px-5 py-3.5 text-sm font-bold text-white">تابع إلى إعداد المزاد <ChevronLeft size={17} /></button></> : <><div className="flex items-center justify-between"><div><p className="text-xs font-bold text-[#d96d46]">الخطوة 2 من 2</p><h2 className="mt-1 font-serif text-3xl">إعدادات المزاد</h2></div><CalendarClock className="text-[#d96d46]" /></div><div className="mt-7 grid gap-5 sm:grid-cols-2"><label className="grid gap-2 text-sm font-semibold">سعر البداية<input value={startingPrice} onChange={(event) => setStartingPrice(event.target.value)} inputMode="decimal" placeholder="مثال: 500" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">سعر الاحتياط <span className="text-xs font-normal text-[#758488]">اختياري</span><input value={reservePrice} onChange={(event) => setReservePrice(event.target.value)} inputMode="decimal" placeholder="مثال: 1000" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold">أقل زيادة<input value={increment} onChange={(event) => setIncrement(event.target.value)} inputMode="decimal" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><div className="rounded-xl border border-[#143039]/10 bg-[#f4f3ed] p-4 text-sm leading-6 text-[#6b7d81]">العملة والسياق سيأتيان من الدولة المختارة عند اتصال واجهة Laravel الحية.</div><label className="grid gap-2 text-sm font-semibold sm:col-span-2">وقت البداية<input value={startTime} onChange={(event) => setStartTime(event.target.value)} type="datetime-local" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label><label className="grid gap-2 text-sm font-semibold sm:col-span-2">وقت النهاية<input value={endTime} onChange={(event) => setEndTime(event.target.value)} type="datetime-local" className="rounded-xl border border-[#143039]/15 px-4 py-3 font-normal outline-none focus:border-[#d96d46]" /></label></div><div className="mt-7 rounded-2xl bg-[#edf0e8] p-4 text-sm leading-6 text-[#59706d]"><strong className="text-[#143039]">ملخص العرض:</strong> {title || "قطعة بلا عنوان"} · {category} · {city} · حالة {condition}. سيخضع العرض الفعلي لمراجعة قبل أن يصبح مزاداً حياً.</div><div className="mt-8 flex flex-col gap-3 sm:flex-row"><button type="button" onClick={() => setStep(1)} className="rounded-xl border border-[#143039]/15 px-5 py-3 text-sm font-bold">العودة للتفاصيل</button><button type="submit" className="flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#d96d46] px-5 py-3 text-sm font-bold text-white"><Gavel size={17} />حفظ مسودة العرض</button></div></>}</form></div></div></section></main>;
}
