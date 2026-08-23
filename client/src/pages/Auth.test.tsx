// @vitest-environment jsdom
import React from "react";
import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

const mocks = vi.hoisted(() => {
  class LaravelApiRequestError extends Error {
    constructor(message: string, public status: number, public validationErrors: Record<string, string[]> = {}) {
      super(message);
    }
  }

  return {
    getLiveMarketplaceCountries: vi.fn(),
    getLiveSellerReferences: vi.fn(),
    loginLiveMarketplace: vi.fn(),
    registerLiveMarketplace: vi.fn(),
    LaravelApiRequestError,
    toastError: vi.fn(),
  };
});

vi.mock("@/lib/marketplaceApi", () => ({
  getLiveMarketplaceCountries: mocks.getLiveMarketplaceCountries,
  getLiveSellerReferences: mocks.getLiveSellerReferences,
  isLiveMarketplaceEnabled: () => true,
  LaravelApiRequestError: mocks.LaravelApiRequestError,
  loginLiveMarketplace: mocks.loginLiveMarketplace,
  registerLiveMarketplace: mocks.registerLiveMarketplace,
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/auth", vi.fn()],
}));

vi.mock("sonner", () => ({
  toast: {
    error: mocks.toastError,
    message: vi.fn(),
    success: vi.fn(),
  },
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

  it("ينقل البريد المسجل مسبقاً إلى تسجيل الدخول بدلاً من رسالة فشل عامة", async () => {
    mocks.getLiveMarketplaceCountries.mockResolvedValue([{ id: 1, code: "SA", name: "المملكة العربية السعودية", currency: { code: "SAR", symbol: "ر.س" } }]);
    mocks.getLiveSellerReferences.mockResolvedValue({ country: { id: 1, name: "المملكة العربية السعودية", code: "SA", timezone: "Asia/Riyadh" }, currency: null, cities: [{ id: 9, name: "الرياض" }], categories: [] });
    mocks.registerLiveMarketplace.mockRejectedValue(new mocks.LaravelApiRequestError("The given data was invalid.", 422, { email: ["The email has already been taken."] }));

    render(<Auth />);
    await screen.findByText("المملكة العربية السعودية");
    fireEvent.click(screen.getByRole("button", { name: "حساب جديد" }));
    fireEvent.change(screen.getByLabelText("الاسم الكامل"), { target: { value: "Sameh" } });
    fireEvent.change(screen.getByLabelText("البريد الإلكتروني"), { target: { value: "sameh@example.test" } });
    const passwordInputs = screen.getAllByLabelText(/كلمة المرور/);
    fireEvent.change(passwordInputs[0], { target: { value: "valid-password-123" } });
    fireEvent.change(passwordInputs[1], { target: { value: "valid-password-123" } });
    fireEvent.submit(screen.getByRole("button", { name: "إنشاء الحساب" }).closest("form")!);

    await waitFor(() => expect(screen.getByText("مرحباً بعودتك")).toBeTruthy());
    expect(screen.getByDisplayValue("sameh@example.test")).toBeTruthy();
  });

  it("يوضح أن الحساب يتبع سوقاً مختلفاً بدلاً من إخفاء سبب الرفض", async () => {
    mocks.getLiveMarketplaceCountries.mockResolvedValue([{ id: 1, code: "SA", name: "المملكة العربية السعودية", currency: { code: "SAR", symbol: "ر.س" } }]);
    mocks.getLiveSellerReferences.mockResolvedValue({ country: { id: 1, name: "المملكة العربية السعودية", code: "SA", timezone: "Asia/Riyadh" }, currency: null, cities: [{ id: 9, name: "الرياض" }], categories: [] });
    mocks.loginLiveMarketplace.mockRejectedValue(new mocks.LaravelApiRequestError("The given data was invalid.", 422, { email: ["This account is unavailable in the selected marketplace."] }));

    render(<Auth />);
    await screen.findByText("المملكة العربية السعودية");
    fireEvent.change(screen.getByLabelText("البريد الإلكتروني"), { target: { value: "sameh@example.test" } });
    fireEvent.change(screen.getByLabelText("كلمة المرور"), { target: { value: "valid-password-123" } });
    fireEvent.submit(screen.getAllByRole("button", { name: "تسجيل الدخول" }).at(-1)!.closest("form")!);

    await waitFor(() => expect(mocks.toastError).toHaveBeenCalledWith("هذا الحساب غير متاح في السوق المحدد.", expect.objectContaining({ description: "اختر السوق الذي أنشأت فيه الحساب ثم أعد المحاولة." })));
  });

  it("يعرض إعادة تحميل السوق عند تعذر الاتصال ثم يعيد تحميل البيانات", async () => {
    const country = { id: 1, code: "SA", name: "المملكة العربية السعودية", currency: { code: "SAR", symbol: "ر.س" } };
    mocks.getLiveMarketplaceCountries.mockRejectedValueOnce(new Error("temporary connection failure")).mockResolvedValueOnce([country]);
    mocks.getLiveSellerReferences.mockResolvedValue({ country: { id: 1, name: "المملكة العربية السعودية", code: "SA", timezone: "Asia/Riyadh" }, currency: null, cities: [{ id: 9, name: "الرياض" }], categories: [] });

    render(<Auth />);

    const retry = await screen.findByRole("button", { name: "إعادة تحميل السوق" });
    fireEvent.click(retry);

    expect(await screen.findByText("المملكة العربية السعودية")).toBeTruthy();
    expect(mocks.getLiveMarketplaceCountries).toHaveBeenCalledTimes(2);
  });
});
