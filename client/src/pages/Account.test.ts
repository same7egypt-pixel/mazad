import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it, vi } from "vitest";

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/account", vi.fn()],
}));

import Account from "./Account";

describe("حساب Marketplace", () => {
  it("لا يعرض قيماً تجريبية عند غياب جلسة Laravel", () => {
    const html = renderToStaticMarkup(React.createElement(Account));

    expect(html).toContain("تسجيل الدخول مطلوب");
    expect(html).toContain("دخول أو إنشاء حساب");
    expect(html).not.toContain("محفوظاتي التجريبية");
  });
});
