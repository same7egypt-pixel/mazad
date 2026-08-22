import { describe, expect, it } from "vitest";
import { demoAccountSnapshot, demoAccountSummaryCards, demoAuctionLots, demoCountryOptions, getDemoAuction } from "./marketplaceDemo";

describe("بيانات عرض Marketplace المؤقتة", () => {
  it("تحتوي على مزادات ودول قابلة للعرض وبديل ثابت لرقم مزاد غير موجود", () => {
    expect(demoAuctionLots).toHaveLength(4);
    expect(demoCountryOptions.every((country) => Number.isInteger(country.apiId) && Boolean(country.name))).toBe(true);
    expect(getDemoAuction(999).id).toBe(demoAuctionLots[0].id);
  });

  it("لا تنشئ مراجعات أو تقييمات أو شهادات مستخدمين مزيفة", () => {
    const serialized = JSON.stringify({ demoAuctionLots, demoAccountSnapshot }).toLowerCase();
    expect(serialized).not.toContain("review");
    expect(serialized).not.toContain("rating");
    expect(serialized).not.toContain("testimonial");
  });

  it("تعرض مؤشرات محفوظات ومزايدات نشطة منفصلة لحساب العرض", () => {
    expect(demoAccountSummaryCards.map((card) => card.key)).toEqual(["watchlist", "active-bids"]);
    expect(demoAccountSummaryCards.every((card) => card.value > 0 && Boolean(card.label))).toBe(true);
  });
});
