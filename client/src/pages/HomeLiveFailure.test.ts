// @vitest-environment jsdom
import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

const api = vi.hoisted(() => ({
  getLiveMarketplaceCountries: vi.fn(),
  getLiveAuctions: vi.fn(),
  isLiveMarketplaceEnabled: vi.fn(() => true),
  getLiveMarketplaceToken: vi.fn(() => null),
  getLiveMarketplaceUser: vi.fn(),
  getSavedMarketplaceCountryId: vi.fn(() => 0),
  clearLiveMarketplaceToken: vi.fn(),
  logoutLiveMarketplace: vi.fn(),
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
  it("لا يعرض بيانات عرض مؤقتة عندما يفشل استدعاء الأسواق الحية", async () => {
    api.getLiveMarketplaceCountries.mockRejectedValueOnce(new Error("offline"));

    render(React.createElement(Home));

    await waitFor(() => expect(screen.getByText("تعذر الاتصال بالمزادات الحية حالياً. لا نعرض أي بيانات بديلة في هذه الواجهة العامة.")).toBeTruthy());
    expect(screen.getByText("المزادات الحية غير متاحة مؤقتاً")).toBeTruthy();
    expect(screen.queryByText("ساعة كرونوغراف كلاسيكية")).toBeNull();
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
