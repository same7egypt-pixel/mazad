// @vitest-environment jsdom
import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

const api = vi.hoisted(() => ({
  getLiveMarketplaceCountries: vi.fn(),
  getLiveAuctions: vi.fn(),
  isLiveMarketplaceEnabled: vi.fn(() => true),
  saveMarketplaceCountryId: vi.fn(),
}));

vi.mock("@/lib/marketplaceApi", () => api);
vi.mock("@/_core/hooks/useAuth", () => ({
  useAuth: () => ({ user: null, isAuthenticated: false, loading: false }),
}));
vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/", vi.fn()],
}));

import Home from "./Home";

describe("فشل Laravel في صفحة الاكتشاف", () => {
  it("يعرض بيانات العرض الموسومة عندما يفشل استدعاء الأسواق الحية", async () => {
    api.getLiveMarketplaceCountries.mockRejectedValueOnce(new Error("offline"));

    render(React.createElement(Home));

    await waitFor(() => expect(screen.getByText("تعذر جلب المزادات الحية؛ نعرض بيانات العرض المؤقتة إلى أن تستعيد خدمة Laravel اتصالها.")).toBeTruthy());
    expect(screen.getAllByText("ساعة كرونوغراف كلاسيكية").length).toBeGreaterThan(0);
  });

  it("يعرض حالة فارغة صريحة عندما يعيد Laravel قائمة مزادات خالية", async () => {
    api.getLiveMarketplaceCountries.mockResolvedValueOnce([
      { id: 1, code: "SA", name: "المملكة العربية السعودية", currency: { symbol: "ر.س" } },
    ]);
    api.getLiveAuctions.mockResolvedValueOnce([]);

    render(React.createElement(Home));

    await waitFor(() => expect(screen.getByText("لا توجد مزادات حية في السوق المحدد حالياً.")).toBeTruthy());
    expect(screen.getByText("لا توجد مزادات حية حالياً")).toBeTruthy();
  });
});
