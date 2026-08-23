// @vitest-environment jsdom
import React from "react";
import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

const mocks = vi.hoisted(() => ({
  clearLiveMarketplaceToken: vi.fn(),
  confirmLiveCashOnDeliveryOrder: vi.fn(),
  confirmLiveCashOnDeliveryReceipt: vi.fn(),
  getLiveAccountSnapshot: vi.fn(),
  getLiveMarketplaceToken: vi.fn(),
  getLiveMarketplaceUser: vi.fn(),
  logoutLiveMarketplace: vi.fn(),
}));

vi.mock("@/lib/marketplaceApi", () => ({
  clearLiveMarketplaceToken: mocks.clearLiveMarketplaceToken,
  confirmLiveCashOnDeliveryOrder: mocks.confirmLiveCashOnDeliveryOrder,
  confirmLiveCashOnDeliveryReceipt: mocks.confirmLiveCashOnDeliveryReceipt,
  getLiveAccountSnapshot: mocks.getLiveAccountSnapshot,
  getLiveMarketplaceToken: mocks.getLiveMarketplaceToken,
  getLiveMarketplaceUser: mocks.getLiveMarketplaceUser,
  logoutLiveMarketplace: mocks.logoutLiveMarketplace,
  getSavedMarketplaceCountryId: () => 7,
  isLiveMarketplaceEnabled: () => true,
}));

vi.mock("wouter", () => ({
  Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => React.createElement("a", { href, ...props }, children),
  useLocation: () => ["/account", vi.fn()],
}));

import Account from "./Account";

describe("الحساب الحي في Laravel", () => {
  afterEach(() => {
    cleanup();
    vi.clearAllMocks();
  });

  it("يعرض الحساب والطلبات والمحفظة من Laravel عندما تكون جلسة Marketplace صالحة", async () => {
    mocks.getLiveMarketplaceToken.mockReturnValue("token-abc");
    mocks.getLiveMarketplaceUser.mockResolvedValue({ id: 44, name: "بائع Laravel", email: "seller@example.test", country_id: 7, city_id: 11 });
    mocks.getLiveAccountSnapshot.mockResolvedValue({
      wallet: { available: "1,250 ر.س", pending: "100 ر.س", label: "محفظتك الحية" },
      orders: [{ reference: "ORD-100", title: "ساعة حية", status: "paid", progress: "بيانات الطلب الحية", tone: "teal" }],
      notifications: [{ title: "طلب حي", detail: "تمت تسوية الطلب.", time: "الآن" }],
    });

    render(<Account />);

    expect((await screen.findAllByText("حسابك نشط")).length).toBeGreaterThan(0);
    expect(screen.getByText("مرحباً، بائع Laravel")).toBeTruthy();
    expect(await screen.findByText("ORD-100")).toBeTruthy();
    expect(screen.getByText("1,250 ر.س")).toBeTruthy();
    expect(screen.getByText("طلب حي")).toBeTruthy();
  });

  it("يوجه إلى صفحة دخول Marketplace عند غياب جلسة المستخدم من دون بيانات عرض", async () => {
    mocks.getLiveMarketplaceToken.mockReturnValue(null);
    render(<Account />);

    expect(await screen.findByText("سجّل الدخول للوصول إلى طلباتك ومحفظتك وإشعاراتك الحية.")).toBeTruthy();
    expect(screen.queryByText("بيانات عرض مؤقتة")).toBeNull();
    expect(screen.getByRole("link", { name: "دخول أو إنشاء حساب" }).getAttribute("href")).toBe("/auth");
  });

  it("يتيح للفائز تأكيد طلب الدفع عند الاستلام واختيار الاستلام الذاتي", async () => {
    mocks.getLiveMarketplaceToken.mockReturnValue("token-abc");
    mocks.getLiveMarketplaceUser.mockResolvedValue({ id: 44, name: "فائز", email: "winner@example.test", country_id: 7, city_id: 11 });
    mocks.getLiveAccountSnapshot.mockResolvedValue({
      wallet: null,
      orders: [{ id: 101, reference: "ORD-101", title: "قطعة فائزة", status: "بانتظار تأكيدك", progress: "أكد طريقة الاستلام", tone: "amber", needsCodConfirmation: true, needsReceiptConfirmation: false, winnerConfirmationExpiresAt: "2026-08-24T12:00:00Z" }],
      notifications: [],
    });
    mocks.confirmLiveCashOnDeliveryOrder.mockResolvedValue(undefined);

    render(<Account />);

    fireEvent.click(await screen.findByRole("button", { name: "تأكيد الطلب" }));

    await waitFor(() => expect(mocks.confirmLiveCashOnDeliveryOrder).toHaveBeenCalledWith(7, 101, { fulfilment_preference: "self_pickup" }));
  });
});
