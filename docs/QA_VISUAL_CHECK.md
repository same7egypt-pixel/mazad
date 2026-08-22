# Visual QA Notes

## 2026-08-22 — Seller flow

The `/sell` route was checked after adding the live seller-product and approved-product scheduling boundary. At the desktop viewport, the first step preserves a readable RTL split layout: the editorial title, product form, visible upload controls, and lifecycle explanation remain within the viewport without overlapping controls. At the 375 px mobile viewport, the same flow stacks in a readable order, has no horizontal overflow, retains clear form labels, and keeps the primary continuation control visible after media selection.

The live-only approved-product list cannot appear in this preview because no valid Laravel API URL and Sanctum session are configured. Its rendering is covered by TypeScript compilation and the existing UI test suite; it still requires a connected staging Laravel service for browser-level success-path validation.
