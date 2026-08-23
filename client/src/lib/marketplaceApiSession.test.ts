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
    vi.restoreAllMocks();
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

  it("يسجل حساب Laravel جديداً مع سياق البلد والمدينة ويحفظ رمز الجلسة", async () => {
    vi.stubEnv("VITE_LARAVEL_API_BASE_URL", "https://api.example.test");
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 201, json: async () => ({ user: liveUser, token: "new-token-abc" }) });
    vi.stubGlobal("fetch", fetchMock);

    const api = await import("./marketplaceApi");
    await expect(api.registerLiveMarketplace(7, {
      city_id: 11,
      name: liveUser.name,
      email: liveUser.email,
      phone: "+966500000000",
      password: "very-secure-password",
      password_confirmation: "very-secure-password",
    })).resolves.toEqual(liveUser);
    expect(api.getLiveMarketplaceToken()).toBe("new-token-abc");

    const [, request] = fetchMock.mock.calls[0] as [string, RequestInit];
    expect(JSON.parse(request.body as string)).toMatchObject({ country_id: 7, city_id: 11, device_name: "Biddfy.ai Web" });
  });

  it("يكمل الدخول داخل الصفحة عندما يمنع وضع الخصوصية تخزين الجلسة", async () => {
    vi.stubEnv("VITE_LARAVEL_API_BASE_URL", "https://api.example.test");
    vi.spyOn(Storage.prototype, "getItem").mockImplementation(() => { throw new DOMException("Storage is blocked", "SecurityError"); });
    vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => { throw new DOMException("Storage is blocked", "SecurityError"); });
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({ ok: true, status: 201, json: async () => ({ user: liveUser, token: "memory-token-abc" }) }));

    const api = await import("./marketplaceApi");
    await expect(api.registerLiveMarketplace(7, {
      city_id: 11,
      name: liveUser.name,
      email: liveUser.email,
      password: "very-secure-password",
      password_confirmation: "very-secure-password",
    })).resolves.toEqual(liveUser);

    expect(api.getLiveMarketplaceToken()).toBe("memory-token-abc");
  });

  it("يحفظ سبب التحقق من Laravel عندما يكون البريد مسجلاً بالفعل", async () => {
    vi.stubEnv("VITE_LARAVEL_API_BASE_URL", "https://api.example.test");
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({
      ok: false,
      status: 422,
      json: async () => ({ message: "The given data was invalid.", errors: { email: ["The email has already been taken."] } }),
    }));

    const api = await import("./marketplaceApi");
    await expect(api.registerLiveMarketplace(7, {
      city_id: 11,
      name: liveUser.name,
      email: liveUser.email,
      password: "very-secure-password",
      password_confirmation: "very-secure-password",
    })).rejects.toMatchObject({ status: 422, validationErrors: { email: ["The email has already been taken."] } });
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
