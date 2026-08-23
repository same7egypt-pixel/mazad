import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/_core/hooks/useAuth", () => ({
  useAuth: () => ({
    user: { name: "بائع العرض" },
    isAuthenticated: true,
    loading: false,
  }),
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/sell", vi.fn()],
}));

import SellSetup from "./SellSetup";

describe("مسار البائع المؤقت", () => {
  it("يعرض حقول المنتج والوسائط وسياق البلد قبل إعداد المزاد", () => {
    const html = renderToStaticMarkup(React.createElement(SellSetup));

    expect(html).toContain("تفاصيل القطعة");
    expect(html).toContain("عنوان القطعة");
    expect(html).toContain("صور القطعة");
    expect(html).toContain("الدولة");
    expect(html).toContain("تابع إلى إعداد المزاد");
  });
});
