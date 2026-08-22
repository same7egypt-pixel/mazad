import { describe, expect, it } from "vitest";

describe("إعداد Laravel API الحي", () => {
  const baseUrl = process.env.VITE_LARAVEL_API_BASE_URL?.replace(/\/$/, "");
  const hasValidBaseUrl = (() => {
    try {
      return Boolean(baseUrl && new URL(baseUrl));
    } catch {
      return false;
    }
  })();

  const integrationTest = hasValidBaseUrl ? it : it.skip;

  integrationTest("يصل إلى مسار الصحة عبر العنوان المهيأ", async () => {
    const response = await fetch(`${baseUrl}/up`);

    expect(response.ok).toBe(true);
  });
});
