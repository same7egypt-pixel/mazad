// @vitest-environment jsdom
import React from "react";
import { cleanup, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

const api = vi.hoisted(() => ({
  clearLiveMarketplaceToken: vi.fn(),
  getLiveAuctions: vi.fn(),
  getLiveMarketplaceCountries: vi.fn(),
  getLiveMarketplaceToken: vi.fn(),
  getLiveMarketplaceUser: vi.fn(),
  getSavedMarketplaceCountryId: vi.fn(),
  isLiveMarketplaceEnabled: vi.fn(),
  logoutLiveMarketplace: vi.fn(),
  saveMarketplaceCountryId: vi.fn(),
}));

vi.mock("@/lib/marketplaceApi", () => api);
vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/", vi.fn()],
}));

import Home from "./Home";

describe("جلسة Marketplace في رأس الصفحة الرئيسية", () => {
  afterEach(() => {
    cleanup();
    vi.clearAllMocks();
  });

  it("يعرض اسم المستخدم ورابط الحساب عندما توجد جلسة Laravel صالحة", async () => {
    api.isLiveMarketplaceEnabled.mockReturnValue(true);
    api.getLiveMarketplaceToken.mockReturnValue("token-1");
    api.getSavedMarketplaceCountryId.mockReturnValue(1);
    api.getLiveMarketplaceCountries.mockResolvedValue([{ id: 1, code: "SA", name: "السعودية", currency: { symbol: "ر.س" } }]);
    api.getLiveAuctions.mockResolvedValue([]);
    api.getLiveMarketplaceUser.mockResolvedValue({ id: 1, name: "Sameh", email: "sameh@example.test", country_id: 1, city_id: 1 });

    render(<Home />);

    await waitFor(() => expect(screen.getByRole("link", { name: "Sameh" }).getAttribute("href")).toBe("/account"));
    expect(screen.getByRole("button", { name: "خروج" })).toBeTruthy();
  });
});
