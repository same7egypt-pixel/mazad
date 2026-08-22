import { describe, expect, it } from "vitest";
import { demoAuctionLots } from "./Home";

describe("واجهة Marketplace التجريبية", () => {
  it("تعرض مزادات فريدة ومكتملة بالحد الأدنى من بيانات العرض", () => {
    expect(demoAuctionLots).toHaveLength(4);
    expect(new Set(demoAuctionLots.map((lot) => lot.id)).size).toBe(demoAuctionLots.length);
    expect(demoAuctionLots.every((lot) => lot.title && lot.price && lot.city)).toBe(true);
  });
});
