import { cn } from "@/lib/utils";
import { AlertTriangle, RotateCcw } from "lucide-react";
import React, { Component, ReactNode } from "react";

interface Props {
  children: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  render() {
    if (this.state.hasError) {
      return (
        <main dir="rtl" className="flex min-h-screen items-center justify-center bg-[#f7f6f1] p-8 text-[#143039]">
          <section className="flex w-full max-w-xl flex-col items-center rounded-[2rem] bg-white p-8 text-center shadow-[0_20px_60px_rgba(20,48,57,.12)]">
            <AlertTriangle
              size={48}
              className="mb-6 shrink-0 text-[#d96d46]"
            />

            <p className="text-xs font-bold tracking-[.14em] text-[#d96d46]">Biddfy.ai</p>
            <h2 className="mt-3 font-serif text-3xl">تعذر فتح الصفحة الآن</h2>
            <p className="mt-3 max-w-md text-sm leading-7 text-[#687a7e]">حدّث الصفحة لإعادة تحميل النسخة الأحدث. إذا استمرت المشكلة، حاول مرة أخرى بعد لحظات.</p>

            <button
              onClick={() => window.location.reload()}
              className="mt-7 flex items-center gap-2 rounded-xl bg-[#12313a] px-5 py-3 text-sm font-bold text-white transition hover:brightness-110"
            >
              <RotateCcw size={16} />
              إعادة تحميل الصفحة
            </button>
          </section>
        </main>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
