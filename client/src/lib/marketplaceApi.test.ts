import { describe, expect, it } from "vitest";
import { isLiveMarketplaceEnabled, mapLaravelAuction } from "./marketplaceApi";

describe("محول Laravel Marketplace", () => {
  it("يحوّل استجابة مزاد Laravel إلى البيانات التي تتطلبها بطاقة الواجهة", () => {
    const listing = mapLaravelAuction({
      id: 12,
      status: "live",
      current_price: "12800.50",
      end_time: new Date(Date.now() + 3_600_000).toISOString(),
      currency: { code: "SAR", symbol: "ر.س" },
      product: {
        id: 7,
        title: "ساعة حيّة",
        description: "تفاصيل من Laravel",
        condition: "excellent",
        city: { name: "الرياض" },
        category: { name: "ساعات" },
      },
    }, "sa");

    expect(listing).toMatchObject({ id: 12, title: "ساعة حيّة", city: "الرياض", countryCode: "sa", category: "ساعات" });
    expect(listing.price).toContain("ر.س");
  });

  it("يعكس تهيئة عنوان API من البيئة", () => {
    expect(isLiveMarketplaceEnabled()).toBe(
      Boolean(process.env.VITE_LARAVEL_API_BASE_URL),
    );
  });
});
