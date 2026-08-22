import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it, vi } from "vitest";
import { demoAuctionLots } from "@/data/marketplaceDemo";

vi.mock("@/_core/hooks/useAuth", () => ({
  useAuth: () => ({ user: null, isAuthenticated: false, loading: false }),
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/", vi.fn()],
}));

import Home from "./Home";

describe("واجهة Marketplace التجريبية", () => {
  it("تعرض مزادات فريدة ومكتملة بالحد الأدنى من بيانات العرض", () => {
    expect(demoAuctionLots).toHaveLength(4);
    expect(new Set(demoAuctionLots.map((lot) => lot.id)).size).toBe(demoAuctionLots.length);
    expect(demoAuctionLots.every((lot) => lot.title && lot.price && lot.city)).toBe(true);
  });

  it("توسم بيانات العرض الاحتياطية عندما لا يكون Laravel الحي مهيأ", () => {
    const html = renderToStaticMarkup(React.createElement(Home));

    expect(html).toContain("بيانات عرض مؤقتة حتى اتصال Laravel");
    expect(html).toContain("ساعة كرونوغراف كلاسيكية");
  });
});
