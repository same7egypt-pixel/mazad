import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it, vi } from "vitest";
import { demoAuctionLots } from "@/data/marketplaceDemo";
import { isLiveMarketplaceEnabled } from "@/lib/marketplaceApi";

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

  it("توضح مصدر العرض وفق حالة تهيئة Laravel", () => {
    const html = renderToStaticMarkup(React.createElement(Home));

    expect(html).toContain(
      isLiveMarketplaceEnabled()
        ? "جارٍ تحميل المزادات الحية"
        : "بيانات عرض مؤقتة حتى اتصال Laravel",
    );
    if (!isLiveMarketplaceEnabled()) expect(html).toContain("ساعة كرونوغراف كلاسيكية");
    expect(html).toContain("دخول أو إنشاء حساب");
    expect(html).toContain("Biddfy.ai");
  });
});
