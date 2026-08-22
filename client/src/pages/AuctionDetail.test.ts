import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/_core/hooks/useAuth", () => ({
  useAuth: () => ({ isAuthenticated: true, loading: false }),
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useRoute: () => [true, { id: "1" }],
}));

import AuctionDetail from "./AuctionDetail";

describe("تفاصيل المزاد مع احتياط API", () => {
  it("تعرض البيانات المؤقتة الموسومة وسجل المزايدات التجريبي عند غياب عنوان Laravel الحي", () => {
    const html = renderToStaticMarkup(React.createElement(AuctionDetail));

    expect(html).toContain("بيانات عرض");
    expect(html).toContain("سجل مزايدات تجريبي");
    expect(html).toContain("ساعة كرونوغراف كلاسيكية");
  });
});
