// @vitest-environment jsdom
import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

const api = vi.hoisted(() => ({
  clearLiveMarketplaceToken: vi.fn(),
  getLiveAuction: vi.fn(),
  getLiveBidActivity: vi.fn(),
  getLiveMarketplaceToken: vi.fn(() => null),
  getLiveMarketplaceUser: vi.fn(),
  isLiveMarketplaceEnabled: vi.fn(() => true),
  placeLiveBid: vi.fn(),
}));

vi.mock("@/lib/marketplaceApi", () => api);
vi.mock("@/_core/hooks/useAuth", () => ({
  useAuth: () => ({ isAuthenticated: true, loading: false }),
}));
vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useRoute: () => [true, { id: "1" }],
}));

import AuctionDetail from "./AuctionDetail";

describe("فشل Laravel في تفاصيل المزاد", () => {
  it("يعيد عرض البيانات التجريبية وسجل المزايدات التجريبي عند فشل جلب المزاد الحي", async () => {
    window.history.pushState({}, "", "/auction/1?countryId=1");
    api.getLiveAuction.mockRejectedValueOnce(new Error("offline"));
    api.getLiveBidActivity.mockRejectedValueOnce(new Error("offline"));

    render(React.createElement(AuctionDetail));

    await waitFor(() => expect(screen.getByText("بيانات عرض")).toBeTruthy());
    expect(screen.getByText("سجل مزايدات تجريبي")).toBeTruthy();
    expect(screen.getByText("ساعة كرونوغراف كلاسيكية")).toBeTruthy();
  });
});
