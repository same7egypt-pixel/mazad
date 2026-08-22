// @vitest-environment jsdom
import React from "react";
import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

const mocks = vi.hoisted(() => ({
  clearLiveMarketplaceToken: vi.fn(),
  createLiveAuction: vi.fn(),
  getLiveMarketplaceToken: vi.fn(),
  getLiveMarketplaceUser: vi.fn(),
  getLiveSellerProducts: vi.fn(),
  getLiveSellerReferences: vi.fn(),
  loginLiveMarketplace: vi.fn(),
  logoutLiveMarketplace: vi.fn(),
  toastError: vi.fn(),
  toastInfo: vi.fn(),
  toastSuccess: vi.fn(),
}));

vi.mock("@/_core/hooks/useAuth", () => ({
  useAuth: () => ({ user: { name: "بائع حي" }, isAuthenticated: true, loading: false }),
}));

vi.mock("@/lib/marketplaceApi", () => ({
  clearLiveMarketplaceToken: mocks.clearLiveMarketplaceToken,
  createLiveAuction: mocks.createLiveAuction,
  createLiveProduct: vi.fn(),
  getLiveMarketplaceToken: mocks.getLiveMarketplaceToken,
  getLiveMarketplaceUser: mocks.getLiveMarketplaceUser,
  getLiveSellerReferences: mocks.getLiveSellerReferences,
  getLiveSellerProducts: mocks.getLiveSellerProducts,
  getSavedMarketplaceCountryId: () => 7,
  isLiveMarketplaceEnabled: () => true,
  loginLiveMarketplace: mocks.loginLiveMarketplace,
  logoutLiveMarketplace: mocks.logoutLiveMarketplace,
  submitLiveProductForReview: vi.fn(),
  uploadLiveProductMedia: vi.fn(),
}));

vi.mock("sonner", () => ({
  toast: { error: mocks.toastError, info: mocks.toastInfo, success: mocks.toastSuccess },
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
}));

import SellSetup from "./SellSetup";

const referenceData = {
  country: { id: 7, code: "SA", name: "Saudi Arabia", timezone: "Asia/Riyadh" },
  currency: { id: 1, name: "Saudi Riyal", code: "SAR", symbol: "ر.س", decimal_places: 2 },
  cities: [{ id: 11, name: "Riyadh" }],
  categories: [{ id: 12, name: "Watches", slug: "watches", parent_id: null }],
};

const products = [
  { id: 101, title: "قطعة معتمدة", status: "approved", condition: "good", city: { name: "Riyadh" }, category: { name: "Watches" }, auction: null },
  { id: 102, title: "قطعة قيد المراجعة", status: "pending_review", condition: "good", city: { name: "Riyadh" }, category: { name: "Watches" }, auction: null },
  { id: 103, title: "قطعة لها مزاد", status: "approved", condition: "good", city: { name: "Riyadh" }, category: { name: "Watches" }, auction: { id: 88, status: "upcoming" } },
];

async function renderReadySellerFlow() {
  mocks.getLiveMarketplaceToken.mockReturnValue("token-abc");
  mocks.getLiveMarketplaceUser.mockResolvedValue({ id: 44, name: "بائع Laravel", email: "seller@example.test", country_id: 7, city_id: 11, status: "active" });
  mocks.getLiveSellerReferences.mockResolvedValue(referenceData);
  mocks.getLiveSellerProducts.mockResolvedValue(products);
  render(<SellSetup />);

  await screen.findByText("منتجاتك الحية");
  fireEvent.change(screen.getByLabelText("عنوان القطعة"), { target: { value: "ساعة معروضة" } });
  fireEvent.change(screen.getByLabelText("الوصف"), { target: { value: "هذا وصف حي كافٍ لتفعيل الانتقال إلى إعدادات المزاد." } });
  fireEvent.click(screen.getByRole("button", { name: "تابع إلى إعداد المزاد" }));
  fireEvent.change(screen.getByLabelText("سعر البداية"), { target: { value: "1000" } });
  fireEvent.change(screen.getByLabelText("أقل زيادة"), { target: { value: "100" } });
  fireEvent.change(screen.getByLabelText("وقت البداية"), { target: { value: "2026-08-23T10:00" } });
  fireEvent.change(screen.getByLabelText("وقت النهاية"), { target: { value: "2026-08-23T12:00" } });
  fireEvent.change(screen.getByLabelText("المنتج المعتمد"), { target: { value: "101" } });
}

describe("مسار البائع الحي", () => {
  afterEach(() => {
    cleanup();
    vi.clearAllMocks();
  });

  it("يعرض المنتج المعتمد غير المرتبط بمزاد كخيار جدولة وحسب", async () => {
    mocks.createLiveAuction.mockResolvedValue({ id: 501, status: "upcoming" });
    await renderReadySellerFlow();

    expect(screen.getByRole("option", { name: "قطعة معتمدة" })).toBeTruthy();
    expect(screen.queryByRole("option", { name: "قطعة قيد المراجعة" })).toBeNull();
    expect(screen.queryByRole("option", { name: "قطعة لها مزاد" })).toBeNull();

    fireEvent.click(screen.getByRole("button", { name: "جدولة المزاد" }));

    await waitFor(() => expect(mocks.createLiveAuction).toHaveBeenCalledWith(7, expect.objectContaining({
      product_id: 101,
      starting_price: "1000",
      minimum_increment: "100",
    })));
    await waitFor(() => expect(screen.getAllByText("مرتبط بمزاد upcoming")).toHaveLength(2));
    expect(mocks.toastSuccess).toHaveBeenCalled();
  });

  it("يعرض خطأً مفهوماً إن رفض Laravel جدولة المنتج المعتمد", async () => {
    mocks.createLiveAuction.mockRejectedValue(new Error("unprocessable"));
    await renderReadySellerFlow();

    fireEvent.click(screen.getByRole("button", { name: "جدولة المزاد" }));

    await waitFor(() => expect(mocks.toastError).toHaveBeenCalledWith("تعذر جدولة المزاد. تحقق من اعتماد المنتج، صلاحية إنشاء المزادات، والتوقيتات المدخلة."));
  });

  it("يسجل دخول Laravel صراحةً قبل فتح التدفق الحي للبائع", async () => {
    const user = { id: 44, name: "بائع Laravel", email: "seller@example.test", country_id: 7, city_id: 11, status: "active" };
    mocks.getLiveMarketplaceToken.mockReturnValue(null);
    mocks.loginLiveMarketplace.mockResolvedValue(user);
    mocks.getLiveSellerReferences.mockResolvedValue(referenceData);
    mocks.getLiveSellerProducts.mockResolvedValue([]);
    render(<SellSetup />);

    await screen.findByText("سجّل دخول Laravel لتفعيل الإرسال الحي");
    fireEvent.change(screen.getByLabelText("البريد الإلكتروني"), { target: { value: user.email } });
    fireEvent.change(screen.getByLabelText("كلمة المرور"), { target: { value: "secret-password" } });
    fireEvent.click(screen.getByRole("button", { name: "دخول Laravel" }));

    await waitFor(() => expect(mocks.loginLiveMarketplace).toHaveBeenCalledWith(7, user.email, "secret-password"));
    expect(await screen.findByText("متصل باسم بائع Laravel")).toBeTruthy();
    expect(mocks.toastSuccess).toHaveBeenCalled();
  });
});
