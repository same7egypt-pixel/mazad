// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from "vitest";

const liveUser = {
  id: 44,
  name: "بائع Laravel",
  email: "seller@example.test",
  country_id: 7,
  city_id: 11,
  status: "active",
};

describe("جلسة Laravel Marketplace", () => {
  afterEach(() => {
    window.sessionStorage.clear();
    vi.unstubAllEnvs();
    vi.unstubAllGlobals();
    vi.resetModules();
  });

  it("يحفظ رمز الدخول في sessionStorage ويرسله إلى نقطة المستخدم المحمية", async () => {
    vi.stubEnv("VITE_LARAVEL_API_BASE_URL", "https://api.example.test");
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({ ok: true, json: async () => ({ user: liveUser, token: "token-abc" }) })
      .mockResolvedValueOnce({ ok: true, json: async () => liveUser });
    vi.stubGlobal("fetch", fetchMock);

    const api = await import("./marketplaceApi");
    await expect(api.loginLiveMarketplace(7, liveUser.email, "secret-password")).resolves.toEqual(liveUser);
    expect(api.getLiveMarketplaceToken()).toBe("token-abc");

    await expect(api.getLiveMarketplaceUser(7)).resolves.toEqual(liveUser);

    const [, request] = fetchMock.mock.calls[1] as [string, RequestInit];
    const headers = request.headers as Headers;
    expect(headers.get("Authorization")).toBe("Bearer token-abc");
    expect(headers.get("X-Marketplace-Country")).toBe("7");
  });

  it("ينهي الجلسة الحية عند استجابة تسجيل الخروج 204 ويمسح الرمز المحلي", async () => {
    vi.stubEnv("VITE_LARAVEL_API_BASE_URL", "https://api.example.test");
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({ user: liveUser, token: "token-abc" }) })
      .mockResolvedValueOnce({ ok: true, status: 204 });
    vi.stubGlobal("fetch", fetchMock);

    const api = await import("./marketplaceApi");
    await api.loginLiveMarketplace(7, liveUser.email, "secret-password");
    await expect(api.logoutLiveMarketplace(7)).resolves.toBeUndefined();

    expect(api.getLiveMarketplaceToken()).toBeNull();
  });

  it("يمسح رمز الجلسة عند رفض Laravel للطلب المحمي برمز منتهٍ", async () => {
    vi.stubEnv("VITE_LARAVEL_API_BASE_URL", "https://api.example.test");
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({ user: liveUser, token: "token-abc" }) })
      .mockResolvedValueOnce({ ok: false, status: 401 });
    vi.stubGlobal("fetch", fetchMock);

    const api = await import("./marketplaceApi");
    await api.loginLiveMarketplace(7, liveUser.email, "secret-password");
    await expect(api.getLiveMarketplaceUser(7)).rejects.toThrow("status 401");

    expect(api.getLiveMarketplaceToken()).toBeNull();
  });
});
