// @vitest-environment jsdom
import React from "react";
import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

const mocks = vi.hoisted(() => ({
  getLiveMarketplaceCountries: vi.fn(),
  getLiveSellerReferences: vi.fn(),
  loginLiveMarketplace: vi.fn(),
  registerLiveMarketplace: vi.fn(),
}));

vi.mock("@/lib/marketplaceApi", () => ({
  getLiveMarketplaceCountries: mocks.getLiveMarketplaceCountries,
  getLiveSellerReferences: mocks.getLiveSellerReferences,
  isLiveMarketplaceEnabled: () => true,
  loginLiveMarketplace: mocks.loginLiveMarketplace,
  registerLiveMarketplace: mocks.registerLiveMarketplace,
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/auth", vi.fn()],
}));

import Auth from "./Auth";

describe("صفحة دخول Marketplace", () => {
  afterEach(() => {
    cleanup();
    vi.clearAllMocks();
  });

  it("تعرض دخول Laravel وسوقاً حياً وتنتقل إلى نموذج الحساب الجديد", async () => {
    mocks.getLiveMarketplaceCountries.mockResolvedValue([{ id: 1, code: "SA", name: "المملكة العربية السعودية", currency: { code: "SAR", symbol: "ر.س" } }]);
    mocks.getLiveSellerReferences.mockResolvedValue({ country: { id: 1, name: "المملكة العربية السعودية", code: "SA", timezone: "Asia/Riyadh" }, currency: null, cities: [{ id: 9, name: "الرياض" }], categories: [] });

    render(<Auth />);

    expect(await screen.findByText("المملكة العربية السعودية")).toBeTruthy();
    expect(screen.getByText("مرحباً بعودتك")).toBeTruthy();
    screen.getByRole("button", { name: "حساب جديد" }).click();
    expect(await screen.findByRole("button", { name: "إنشاء الحساب" })).toBeTruthy();
    expect(screen.getByLabelText("المدينة")).toBeTruthy();
  });
});
