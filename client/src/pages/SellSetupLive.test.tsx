// @vitest-environment jsdom
import React from "react";
import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

const mocks = vi.hoisted(() => ({
  clearLiveMarketplaceToken: vi.fn(),
  createLiveAuction: vi.fn(),
  createLiveProduct: vi.fn(),
  getLiveMarketplaceToken: vi.fn(),
  getLiveMarketplaceUser: vi.fn(),
  getLiveSellerProducts: vi.fn(),
  getLiveSellerReferences: vi.fn(),
  loginLiveMarketplace: vi.fn(),
  logoutLiveMarketplace: vi.fn(),
  registerLiveMarketplace: vi.fn(),
  submitLiveProductForReview: vi.fn(),
  toastError: vi.fn(),
  toastInfo: vi.fn(),
  toastSuccess: vi.fn(),
  uploadLiveProductMedia: vi.fn(),
  navigate: vi.fn(),
}));

vi.mock("@/_core/hooks/useAuth", () => ({
  useAuth: () => ({ user: { name: "بائع حي" }, isAuthenticated: true, loading: false }),
}));

vi.mock("@/lib/marketplaceApi", () => ({
  clearLiveMarketplaceToken: mocks.clearLiveMarketplaceToken,
  createLiveAuction: mocks.createLiveAuction,
  createLiveProduct: mocks.createLiveProduct,
  getLiveMarketplaceToken: mocks.getLiveMarketplaceToken,
  getLiveMarketplaceUser: mocks.getLiveMarketplaceUser,
  getLiveSellerReferences: mocks.getLiveSellerReferences,
  getLiveSellerProducts: mocks.getLiveSellerProducts,
  getSavedMarketplaceCountryId: () => 7,
  isLiveMarketplaceEnabled: () => true,
  loginLiveMarketplace: mocks.loginLiveMarketplace,
  logoutLiveMarketplace: mocks.logoutLiveMarketplace,
  registerLiveMarketplace: mocks.registerLiveMarketplace,
  submitLiveProductForReview: mocks.submitLiveProductForReview,
  uploadLiveProductMedia: mocks.uploadLiveProductMedia,
}));

vi.mock("sonner", () => ({
  toast: { error: mocks.toastError, info: mocks.toastInfo, success: mocks.toastSuccess },
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/sell", mocks.navigate],
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
  await waitFor(() => expect(screen.getByText("3 منتج")).toBeTruthy());
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
    mocks.createLiveAuction.mockReset();
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

  it("يثبت التوقيتات المقترحة في حالة النموذج ويعرضها باتجاه واضح", async () => {
    await renderReadySellerFlow();

    const start = screen.getByLabelText("وقت البداية") as HTMLInputElement;
    const end = screen.getByLabelText("وقت النهاية") as HTMLInputElement;

    expect(start.value).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/);
    expect(end.value).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/);
    expect(start.getAttribute("dir")).toBe("ltr");
    expect(end.getAttribute("lang")).toBe("en-GB");
  });

  it("يرسل المنتج للمراجعة دون اشتراط جدولة المزاد قبل اعتماده", async () => {
    mocks.createLiveProduct.mockResolvedValue({ id: 901 });
    mocks.submitLiveProductForReview.mockResolvedValue(undefined);
    await renderReadySellerFlow();
    fireEvent.change(screen.getByLabelText("وقت البداية"), { target: { value: "" } });
    fireEvent.change(screen.getByLabelText("وقت النهاية"), { target: { value: "" } });

    fireEvent.click(screen.getByRole("button", { name: "إرسال المنتج للمراجعة" }));

    await waitFor(() => expect(mocks.createLiveProduct).toHaveBeenCalledWith(7, expect.objectContaining({
      title: "ساعة معروضة",
      condition: "good",
    })));
    expect(mocks.toastError).not.toHaveBeenCalledWith("أكمل سعر البداية والزيادة وتوقيت البداية والنهاية.");
    await waitFor(() => expect(mocks.navigate).toHaveBeenCalledWith("/"));
  });

  it("يعرض خطأً مفهوماً إن رفض Laravel جدولة المنتج المعتمد", async () => {
    mocks.createLiveAuction.mockReset();
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

    await screen.findByText("سجّل الدخول لتفعيل الإرسال");
    fireEvent.change(screen.getByLabelText("البريد الإلكتروني"), { target: { value: user.email } });
    fireEvent.change(screen.getByLabelText("كلمة المرور"), { target: { value: "secret-password" } });
    fireEvent.click(screen.getByRole("button", { name: "تسجيل الدخول" }));

    await waitFor(() => expect(mocks.loginLiveMarketplace).toHaveBeenCalledWith(7, user.email, "secret-password"));
    expect(await screen.findByText("متصل باسم بائع Laravel")).toBeTruthy();
    expect(mocks.toastSuccess).toHaveBeenCalled();
  });

  it("ينشئ حساب Laravel جديداً من مسار البائع ويفتح الجلسة الحية", async () => {
    const user = { id: 45, name: "بائع جديد", email: "new-seller@example.test", country_id: 7, city_id: 11, status: "active" };
    mocks.getLiveMarketplaceToken.mockReturnValue(null);
    mocks.getLiveSellerReferences.mockResolvedValue(referenceData);
    mocks.getLiveSellerProducts.mockResolvedValue([]);
    mocks.registerLiveMarketplace.mockResolvedValue(user);
    render(<SellSetup />);

    await screen.findByText("سجّل الدخول لتفعيل الإرسال");
    fireEvent.click(screen.getByRole("button", { name: "حساب جديد" }));
    fireEvent.change(screen.getByLabelText("الاسم الكامل"), { target: { value: user.name } });
    fireEvent.change(screen.getByLabelText("البريد الإلكتروني"), { target: { value: user.email } });
    fireEvent.change(screen.getAllByLabelText(/كلمة المرور/)[0], { target: { value: "very-secure-password" } });
    fireEvent.change(screen.getByLabelText("تأكيد كلمة المرور"), { target: { value: "very-secure-password" } });
    fireEvent.click(screen.getByRole("button", { name: "إنشاء حساب" }));

    await waitFor(() => expect(mocks.registerLiveMarketplace).toHaveBeenCalledWith(7, expect.objectContaining({
      city_id: 11,
      name: user.name,
      email: user.email,
      password: "very-secure-password",
    })));
    expect(await screen.findByText("متصل باسم بائع جديد")).toBeTruthy();
    expect(mocks.toastSuccess).toHaveBeenCalled();
  });

  it("يعرض فشل Laravel عند رفض التسجيل ولا يدعي فتح جلسة جديدة", async () => {
    mocks.getLiveMarketplaceToken.mockReturnValue(null);
    mocks.getLiveSellerReferences.mockResolvedValue(referenceData);
    mocks.getLiveSellerProducts.mockResolvedValue([]);
    mocks.registerLiveMarketplace.mockRejectedValue(new Error("duplicate-email"));
    render(<SellSetup />);

    await screen.findByText("سجّل الدخول لتفعيل الإرسال");
    fireEvent.click(screen.getByRole("button", { name: "حساب جديد" }));
    fireEvent.change(screen.getByLabelText("الاسم الكامل"), { target: { value: "بائع مكرر" } });
    fireEvent.change(screen.getByLabelText("البريد الإلكتروني"), { target: { value: "duplicate@example.test" } });
    fireEvent.change(screen.getAllByLabelText(/كلمة المرور/)[0], { target: { value: "very-secure-password" } });
    fireEvent.change(screen.getByLabelText("تأكيد كلمة المرور"), { target: { value: "very-secure-password" } });
    fireEvent.click(screen.getByRole("button", { name: "إنشاء حساب" }));

    await waitFor(() => expect(mocks.toastError).toHaveBeenCalledWith("تعذر إنشاء الحساب. تحقق من البيانات والبريد والسوق المختار."));
    expect(screen.queryByText(/متصل باسم/)).toBeNull();
  });
});
