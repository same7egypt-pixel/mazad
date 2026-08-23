// @vitest-environment jsdom

import React from "react";
import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

vi.mock("@/lib/marketplaceApi", () => ({
  clearLiveMarketplaceToken: vi.fn(),
  getLiveAuctions: vi.fn(),
  getLiveMarketplaceCountries: vi.fn(),
  getLiveMarketplaceToken: vi.fn(() => null),
  getLiveMarketplaceUser: vi.fn(),
  getSavedMarketplaceCountryId: vi.fn(() => 0),
  isLiveMarketplaceEnabled: vi.fn(() => false),
  logoutLiveMarketplace: vi.fn(),
  saveMarketplaceCountryId: vi.fn(),
}));

vi.mock("sonner", () => ({ toast: { message: vi.fn(), success: vi.fn() } }));
vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/", vi.fn()],
}));

import Home from "./Home";

describe("تصفية وفرز المزادات", () => {
  afterEach(() => cleanup());

  it("يصفي المزادات بالمدينة ويعيد ضبط الخيارات", () => {
    render(<Home />);

    fireEvent.click(screen.getByRole("button", { name: /خيارات التصفية والفرز/ }));
    fireEvent.change(screen.getByLabelText("المدينة"), { target: { value: "جدة" } });

    expect(screen.getByText("تكوين معاصر، إصدار محدود")).toBeTruthy();
    expect(screen.getAllByRole("heading", { level: 3 }).some((heading) => heading.textContent === "ساعة كرونوغراف كلاسيكية")).toBe(false);

    fireEvent.click(screen.getByRole("button", { name: "مسح الكل" }));
    expect(screen.getAllByRole("heading", { level: 3 }).some((heading) => heading.textContent === "ساعة كرونوغراف كلاسيكية")).toBe(true);
  });

  it("يفرز النتائج حسب السعر الأقل أولاً", () => {
    render(<Home />);

    fireEvent.click(screen.getByRole("button", { name: /خيارات التصفية والفرز/ }));
    fireEvent.change(screen.getByLabelText("الفرز"), { target: { value: "price-low" } });

    const auctionHeadings = screen.getAllByRole("heading", { level: 3 });
    expect(auctionHeadings[0].textContent).toContain("مصباح إيطالي من السبعينات");
  });
});
