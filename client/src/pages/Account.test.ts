import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/_core/hooks/useAuth", () => ({
  useAuth: () => ({
    user: { name: "مستخدم العرض" },
    isAuthenticated: true,
    loading: false,
  }),
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
}));

import Account from "./Account";

describe("حساب Marketplace ببيانات العرض", () => {
  it("يعرض مؤشري المحفوظات والمزايدات النشطة للمستخدم المسجل", () => {
    const html = renderToStaticMarkup(React.createElement(Account));

    expect(html).toContain("محفوظاتي التجريبية");
    expect(html).toContain("مزايداتي النشطة");
    expect(html).toContain("مزادات محفوظة للمتابعة");
    expect(html).toContain("مزادات ما زالت مفتوحة");
  });
});
