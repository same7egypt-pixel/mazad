// @vitest-environment jsdom

import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import ErrorBoundary from "./ErrorBoundary";

function BrokenPage(): never {
  throw new Error("render failure");
}

describe("حاجز أخطاء الواجهة", () => {
  it("يعرض واجهة استعادة عربية بدلاً من صفحة فارغة", () => {
    vi.spyOn(console, "error").mockImplementation(() => undefined);

    render(<ErrorBoundary><BrokenPage /></ErrorBoundary>);

    expect(screen.getByText("تعذر فتح الصفحة الآن")).toBeTruthy();
    expect(screen.getByRole("button", { name: "إعادة تحميل الصفحة" })).toBeTruthy();
  });
});
