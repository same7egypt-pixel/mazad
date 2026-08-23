import type { DemoAuction } from "@/data/marketplaceDemo";

const visualThemes = [
  { paint: "from-[#10242c] via-[#466978] to-[#e5d0a1]", mark: "⌁" },
  { paint: "from-[#ce6a4b] via-[#f1c46f] to-[#183d58]", mark: "◒" },
  { paint: "from-[#292a2d] via-[#6e5c47] to-[#c8b69c]", mark: "◉" },
  { paint: "from-[#754631] via-[#bd8565] to-[#e5cfb2]", mark: "◌" },
];

type LaravelPaginator<T> = { data: T[] };

type LaravelProduct = {
  id: number;
  title: string;
  description: string;
  condition: string;
  city?: { name?: string | null } | null;
  category?: { name?: string | null } | null;
};

type LaravelAuction = {
  id: number;
  status: "upcoming" | "live" | string;
  current_price: string | number;
  end_time: string;
  currency?: { code?: string | null; symbol?: string | null } | null;
  product?: LaravelProduct | null;
};

type LaravelBid = { id: number; amount: string | number; created_at: string };

export type MarketplaceCountry = { id: number; code: string; name: string; currency?: { code?: string | null; symbol?: string | null } | null };

const marketplaceCountryStorageKey = "mazad.marketplace-country-id";
const laravelApiTokenStorageKey = "mazad.laravel-api-token";

// Public trial endpoint only. VITE_LARAVEL_API_BASE_URL takes precedence for staging or production replacements.
const deployedTrialLaravelApiBaseUrl = "https://mazad-trial-api.onrender.com";

function normalizeBaseUrl(value: string | undefined): string | null {
  if (!value) return null;
  try {
    const url = new URL(value);
    return url.toString().replace(/\/$/, "");
  } catch {
    return null;
  }
}

export function resolveLaravelApiBaseUrl(configuredUrl?: string): string | null {
  return normalizeBaseUrl(configuredUrl) || normalizeBaseUrl(deployedTrialLaravelApiBaseUrl);
}

export const laravelApiBaseUrl = resolveLaravelApiBaseUrl(import.meta.env.VITE_LARAVEL_API_BASE_URL);

export function isLiveMarketplaceEnabled(): boolean {
  return laravelApiBaseUrl !== null;
}

export function saveMarketplaceCountryId(countryId: number): void {
  if (typeof window !== "undefined" && countryId > 0) window.localStorage.setItem(marketplaceCountryStorageKey, String(countryId));
}

export function getSavedMarketplaceCountryId(): number {
  if (typeof window === "undefined") return 0;
  const countryId = Number(window.localStorage.getItem(marketplaceCountryStorageKey));
  return Number.isInteger(countryId) && countryId > 0 ? countryId : 0;
}

export type LiveMarketplaceUser = {
  id: number;
  name: string;
  email: string;
  country_id: number | null;
  city_id: number | null;
  status?: string;
};

export type LiveMarketplaceRegistrationInput = {
  city_id: number;
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
};

export function getLiveMarketplaceToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.sessionStorage.getItem(laravelApiTokenStorageKey);
}

function saveLiveMarketplaceToken(token: string): void {
  if (typeof window !== "undefined") window.sessionStorage.setItem(laravelApiTokenStorageKey, token);
}

export function clearLiveMarketplaceToken(): void {
  if (typeof window !== "undefined") window.sessionStorage.removeItem(laravelApiTokenStorageKey);
}

function formatMoney(amount: string | number, currency?: LaravelAuction["currency"]): string {
  const numeric = Number(amount);
  const formatted = Number.isFinite(numeric) ? new Intl.NumberFormat("ar-SA", { maximumFractionDigits: 2 }).format(numeric) : String(amount);
  return `${formatted} ${currency?.symbol || currency?.code || ""}`.trim();
}

function formatRemainingTime(endTime: string, status: string): string {
  if (status === "upcoming") return "قريباً";
  const milliseconds = new Date(endTime).getTime() - Date.now();
  if (!Number.isFinite(milliseconds) || milliseconds <= 0) return "انتهى";
  const totalMinutes = Math.floor(milliseconds / 60_000);
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;
  return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
}

export function mapLaravelAuction(auction: LaravelAuction, countryCode: string): DemoAuction {
  const visual = visualThemes[auction.id % visualThemes.length];
  const product = auction.product;

  return {
    id: auction.id,
    category: product?.category?.name || "مزاد",
    title: product?.title || `مزاد #${auction.id}`,
    city: product?.city?.name || "غير محددة",
    countryCode,
    price: formatMoney(auction.current_price, auction.currency),
    time: formatRemainingTime(auction.end_time, auction.status),
    paint: visual.paint,
    mark: visual.mark,
    condition: product?.condition || "تحتاج معاينة",
    description: product?.description || "تفاصيل المنتج ستظهر عند وصول بيانات Laravel الحية.",
    sellerLabel: "بيانات البائع متاحة بعد ربط الحساب",
    shippingLabel: "يتحدد مسار الشحن بعد حالة الطلب",
  };
}

async function marketplaceRequest<T>(path: string, countryId?: number, init?: RequestInit): Promise<T> {
  if (!laravelApiBaseUrl) throw new Error("Laravel API URL is not configured.");

  const headers = new Headers(init?.headers);
  headers.set("Accept", "application/json");
  if (countryId) headers.set("X-Marketplace-Country", String(countryId));
  const token = getLiveMarketplaceToken();
  if (token) headers.set("Authorization", `Bearer ${token}`);

  const response = await fetch(`${laravelApiBaseUrl}${path}`, {
    credentials: "include",
    ...init,
    headers,
  });

  if (!response.ok) {
    if (response.status === 401) clearLiveMarketplaceToken();
    throw new Error(`Laravel API request failed with status ${response.status}.`);
  }
  if (response.status === 204) return undefined as T;

  return response.json() as Promise<T>;
}

export async function loginLiveMarketplace(countryId: number, email: string, password: string): Promise<LiveMarketplaceUser> {
  const payload = await marketplaceRequest<{ user: LiveMarketplaceUser; token: string }>("/api/auth/login", countryId, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password, device_name: "Mazad Marketplace Web" }),
  });

  saveLiveMarketplaceToken(payload.token);
  return payload.user;
}

export async function registerLiveMarketplace(countryId: number, input: LiveMarketplaceRegistrationInput): Promise<LiveMarketplaceUser> {
  const payload = await marketplaceRequest<{ user: LiveMarketplaceUser; token: string }>("/api/auth/register", countryId, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      ...input,
      country_id: countryId,
      device_name: "Mazad Marketplace Web",
    }),
  });

  saveLiveMarketplaceToken(payload.token);
  return payload.user;
}

export async function getLiveMarketplaceUser(countryId: number): Promise<LiveMarketplaceUser> {
  return marketplaceRequest<LiveMarketplaceUser>("/api/user", countryId);
}

export async function logoutLiveMarketplace(countryId: number): Promise<void> {
  try {
    await marketplaceRequest<void>("/api/auth/logout", countryId, { method: "POST" });
  } finally {
    clearLiveMarketplaceToken();
  }
}

export type SellerReferenceData = {
  country: { id: number; name: string; code: string; timezone: string };
  currency: { id: number; name: string; code: string; symbol: string; decimal_places: number } | null;
  cities: Array<{ id: number; name: string }>;
  categories: Array<{ id: number; name: string; slug: string; parent_id: number | null }>;
};

export type LiveProductInput = { city_id: number; category_id: number; title: string; description: string; condition: "new" | "like_new" | "good" | "fair" | "poor" };

export type LiveSellerProduct = {
  id: number;
  title: string;
  status: string;
  condition: string;
  city?: { name?: string | null } | null;
  category?: { name?: string | null } | null;
  auction?: { id: number; status: string } | null;
};

export type LiveAuctionInput = {
  product_id: number;
  starting_price: string;
  reserve_price?: string;
  minimum_increment: string;
  start_time: string;
  end_time: string;
};

export async function getLiveSellerReferences(countryId: number): Promise<SellerReferenceData> {
  return marketplaceRequest<SellerReferenceData>(`/api/marketplaces/${countryId}/references`);
}

export async function createLiveProduct(countryId: number, input: LiveProductInput): Promise<{ id: number; status: string }> {
  const payload = await marketplaceRequest<{ product: { id: number; status: string } }>("/api/products", countryId, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(input),
  });

  return payload.product;
}

export async function submitLiveProductForReview(countryId: number, productId: number): Promise<{ id: number; status: string }> {
  const payload = await marketplaceRequest<{ product: { id: number; status: string } }>(`/api/products/${productId}/submit-for-review`, countryId, { method: "POST" });
  return payload.product;
}

export async function uploadLiveProductMedia(countryId: number, productId: number, file: File): Promise<void> {
  const formData = new FormData();
  formData.append("file", file);
  await marketplaceRequest<{ media: { id: number } }>(`/api/products/${productId}/media`, countryId, {
    method: "POST",
    body: formData,
  });
}

export async function getLiveSellerProducts(countryId: number): Promise<LiveSellerProduct[]> {
  const payload = await marketplaceRequest<LaravelPaginator<LiveSellerProduct>>("/api/my/products", countryId);
  return payload.data;
}

export async function createLiveAuction(countryId: number, input: LiveAuctionInput): Promise<{ id: number; status: string }> {
  const payload = await marketplaceRequest<{ auction: { id: number; status: string } }>("/api/auctions", countryId, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(input),
  });

  return payload.auction;
}

export async function getLiveMarketplaceCountries(): Promise<MarketplaceCountry[]> {
  const payload = await marketplaceRequest<{ data?: MarketplaceCountry[]; countries?: MarketplaceCountry[] }>("/api/marketplaces/countries");
  return payload.countries || payload.data || [];
}

export async function getLiveAuctions(country: MarketplaceCountry): Promise<DemoAuction[]> {
  const payload = await marketplaceRequest<LaravelPaginator<LaravelAuction>>("/api/auctions", country.id);
  return payload.data.map((auction) => mapLaravelAuction(auction, country.code.toLowerCase()));
}

export async function getLiveAuction(auctionId: number, countryId: number, countryCode = "live"): Promise<DemoAuction> {
  const payload = await marketplaceRequest<{ auction: LaravelAuction }>(`/api/auctions/${auctionId}`, countryId);
  return mapLaravelAuction(payload.auction, countryCode);
}

export async function getLiveBidActivity(auctionId: number, countryId: number): Promise<Array<{ amount: string; time: string; label: string }>> {
  const payload = await marketplaceRequest<LaravelPaginator<LaravelBid>>(`/api/auctions/${auctionId}/bids`, countryId);

  return payload.data.map((bid) => ({
    amount: new Intl.NumberFormat("ar-SA", { maximumFractionDigits: 2 }).format(Number(bid.amount)),
    time: new Intl.DateTimeFormat("ar-SA", { dateStyle: "short", timeStyle: "short" }).format(new Date(bid.created_at)),
    label: `مزايدة #${bid.id}`,
  }));
}

export async function placeLiveBid(countryId: number, auctionId: number, amount: string): Promise<{ id: number; amount: string | number }> {
  const payload = await marketplaceRequest<{ bid: { id: number; amount: string | number } }>(`/api/auctions/${auctionId}/bids`, countryId, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ amount }),
  });

  return payload.bid;
}

type LaravelWallet = { available_balance: string | number; pending_balance: string | number; currency?: { code?: string | null; symbol?: string | null } | null };
type LaravelOrder = { id: number; status: string; auction?: { product?: { title?: string | null } | null } | null };
type LaravelNotification = { id: string; data?: { title?: string; message?: string; country_id?: number } | string | null; created_at: string };

export type LiveAccountSnapshot = {
  wallet: { available: string; pending: string; label: string } | null;
  orders: Array<{ reference: string; title: string; status: string; progress: string; tone: "amber" | "teal" }>;
  notifications: Array<{ title: string; detail: string; time: string }>;
};

function statusTone(status: string): "amber" | "teal" {
  return ["paid", "shipped", "ready_for_pickup", "completed"].includes(status) ? "teal" : "amber";
}

export async function getLiveAccountSnapshot(countryId: number): Promise<LiveAccountSnapshot> {
  const [walletPayload, ordersPayload, notificationsPayload] = await Promise.all([
    marketplaceRequest<{ wallets: LaravelWallet[] }>("/api/wallets", countryId),
    marketplaceRequest<LaravelPaginator<LaravelOrder>>("/api/orders", countryId),
    marketplaceRequest<LaravelPaginator<LaravelNotification>>("/api/notifications", countryId),
  ]);
  const primaryWallet = walletPayload.wallets[0] || null;

  return {
    wallet: primaryWallet ? {
      available: formatMoney(primaryWallet.available_balance, primaryWallet.currency),
      pending: formatMoney(primaryWallet.pending_balance, primaryWallet.currency),
      label: "محفظتك الحية",
    } : null,
    orders: ordersPayload.data.map((order) => ({
      reference: `ORD-${order.id}`,
      title: order.auction?.product?.title || `طلب #${order.id}`,
      status: order.status,
      progress: "بيانات الطلب الحية",
      tone: statusTone(order.status),
    })),
    notifications: notificationsPayload.data.map((notice) => {
      const data = typeof notice.data === "string" ? JSON.parse(notice.data) as LaravelNotification["data"] : notice.data;
      return {
        title: typeof data === "object" && data?.title ? data.title : "تحديث في الحساب",
        detail: typeof data === "object" && data?.message ? data.message : "إشعار حي من منصة مِزَاد.",
        time: new Intl.DateTimeFormat("ar-SA", { dateStyle: "short", timeStyle: "short" }).format(new Date(notice.created_at)),
      };
    }),
  };
}
