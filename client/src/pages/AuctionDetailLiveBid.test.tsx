// @vitest-environment jsdom
import React from "react";
import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

const api = vi.hoisted(() => ({
  clearLiveMarketplaceToken: vi.fn(),
  getLiveAuction: vi.fn(),
  getLiveBidActivity: vi.fn(),
  getLiveMarketplaceToken: vi.fn(),
  getLiveMarketplaceUser: vi.fn(),
  placeLiveBid: vi.fn(),
}));
const toast = vi.hoisted(() => ({ error: vi.fn(), info: vi.fn(), message: vi.fn(), success: vi.fn() }));

vi.mock("@/lib/marketplaceApi", () => ({
  ...api,
  isLiveMarketplaceEnabled: () => true,
}));
vi.mock("sonner", () => ({ toast }));
vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useRoute: () => [true, { id: "12" }],
}));

import AuctionDetail from "./AuctionDetail";

const liveAuction = {
  id: 12,
  category: "ساعات",
  title: "ساعة حية",
  city: "الرياض",
  countryCode: "sa",
  price: "12,800 ر.س",
  time: "00:45",
  paint: "from-[#10242c] via-[#466978] to-[#e5d0a1]",
  mark: "⌁",
  condition: "ممتازة",
  description: "تفاصيل مزاد حية قادمة من Laravel.",
  sellerLabel: "بائع حي",
  shippingLabel: "شحن حي",
};

function arrangeLiveAuction() {
  api.getLiveMarketplaceToken.mockReturnValue("token-abc");
  api.getLiveMarketplaceUser.mockResolvedValue({ id: 44, name: "مزايد Laravel", email: "bidder@example.test", country_id: 7, city_id: 11 });
  api.getLiveAuction.mockResolvedValue(liveAuction);
  api.getLiveBidActivity.mockResolvedValue([{ amount: "12,800", time: "الآن", label: "مزايدة #1" }]);
  window.history.pushState({}, "", "/auction/12?countryId=7");
}

describe("المزايدة الحية في Marketplace", () => {
  afterEach(() => {
    cleanup();
    vi.clearAllMocks();
  });

  it("يرسل المبلغ إلى Laravel بعد تحقق الجلسة ويحدث العرض من استجابة الخادم", async () => {
    arrangeLiveAuction();
    api.placeLiveBid.mockResolvedValue({ id: 99, amount: "13000" });
    api.getLiveBidActivity
      .mockResolvedValueOnce([{ amount: "12,800", time: "الآن", label: "مزايدة #1" }])
      .mockResolvedValueOnce([{ amount: "13,000", time: "الآن", label: "مزايدة #99" }]);

    render(<AuctionDetail />);

    await screen.findByText("ستُرسل المزايدة إلى Laravel باسم مزايد Laravel وتُحدّث من الخادم عند قبولها.");
    fireEvent.change(screen.getByLabelText("قيمة المزايدة"), { target: { value: "13000" } });
    fireEvent.click(screen.getByRole("button", { name: "زايد الآن" }));

    await waitFor(() => expect(api.placeLiveBid).toHaveBeenCalledWith(7, 12, "13000"));
    expect(await screen.findByText("مزايدة #99")).toBeTruthy();
    expect((screen.getByLabelText("قيمة المزايدة") as HTMLInputElement).value).toBe("");
    expect(toast.success).toHaveBeenCalledWith("تم تسجيل المزايدة", expect.any(Object));
  });

  it("يعرض فشل Laravel ولا يدعي نجاح مزايدة عندما يرفض الخادم الطلب", async () => {
    arrangeLiveAuction();
    api.placeLiveBid.mockRejectedValue(new Error("unprocessable"));

    render(<AuctionDetail />);

    await screen.findByText("ستُرسل المزايدة إلى Laravel باسم مزايد Laravel وتُحدّث من الخادم عند قبولها.");
    fireEvent.change(screen.getByLabelText("قيمة المزايدة"), { target: { value: "13000" } });
    fireEvent.click(screen.getByRole("button", { name: "زايد الآن" }));

    await waitFor(() => expect(toast.error).toHaveBeenCalledWith("تعذر تسجيل المزايدة. تحقق من الجلسة، حالة المزاد، والحد الأدنى للمزايدة."));
    expect(toast.success).not.toHaveBeenCalled();
  });
});
