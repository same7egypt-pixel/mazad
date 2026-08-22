# Database Schema Report — Auction Marketplace Foundation

## Design Principles

The schema is PostgreSQL-oriented and uses referential constraints for ownership, country, city, category, currency, auction, order, and wallet relationships. Monetary values use `decimal(18,2)` rather than binary floating point. State is represented by indexed string values at this foundation stage so status transitions can be centralized in domain services and validated by tests before database enum constraints are introduced.

| Area | Primary tables | Key integrity rules |
|---|---|---|
| Identity and scope | `users`, `countries`, `cities`, `currencies` | Users carry nullable country/city fields for global administrators; operational users are country-scoped. A city belongs to one country, and a country has one default currency. |
| Catalog | `categories`, `products`, `product_media` | Products retain seller, country, city, category, and currency context. Media stores disk/path/mime/size metadata only; file bytes remain in S3-compatible storage. |
| Auction | `auctions`, `bids` | One auction per product, indexed country/status/end-time lookup, bidder history, bid count, current price, and optimistic version. |
| Commercial settlement | `orders`, `payments`, `wallets`, `wallet_transactions`, `withdrawals` | An auction has at most one order. Wallets are unique per user/currency. Payment transaction identifiers are unique per gateway when present. |
| Fulfilment and reputation | `shipping_providers`, `shipments`, `reviews` | One shipment per order and one reviewer/order review. Sensitive provider, address, and withdrawal detail fields are application-encrypted text. |
| Control and risk | `notifications`, `audit_logs`, `user_devices`, `user_activities`, `activity_log` | Audit and activity records retain actor, country, IP, user agent, and structured contextual properties where appropriate. |
| Platform operations | `settings`, `permissions`, `roles`, `model_has_roles`, `personal_access_tokens`, `jobs`, `job_batches`, `failed_jobs` | Supports configuration, Sanctum tokens, RBAC, asynchronous work, and Horizon observability. |

## Principal Relationships

| Parent | Relationship | Child |
|---|---|---|
| Country | one-to-many | Cities, users, products, auctions, orders, payments, shipping providers, activity records |
| Currency | one-to-many | Countries, products, auctions, orders, wallets |
| User | one-to-many | Products, bids, sales, purchases, wallets, devices, activities |
| Product | one-to-many / one-to-one | Product media / auction |
| Auction | one-to-many / one-to-one | Bids / order |
| Order | one-to-many / one-to-one | Payments and reviews / shipment |
| Wallet | one-to-many | Wallet transactions and withdrawals |

## Migration Inventory

The verification database contains **11 applied Laravel migrations** and **37 public tables**. The marketplace-specific migrations are ordered after Sanctum, activity logging, and RBAC migrations:

| Migration | Purpose |
|---|---|
| `2026_08_22_150000_create_marketplace_reference_tables` | Currencies, countries, cities, categories, settings, shipping providers, and user country/city foreign keys. |
| `2026_08_22_150100_create_marketplace_commerce_tables` | Products, media, auctions, bids, orders, payments, wallets, and wallet transactions. |
| `2026_08_22_150200_create_marketplace_operations_tables` | Shipments, reviews, notifications, audit logs, fraud foundations, and withdrawals. |

## Notes for the Next Migration Set

Payment-gateway credentials must remain in secret storage rather than database seeders. Country-specific gateway configuration should store only opaque references or encrypted configuration after the gateway boundary is implemented. The auction close job must create an order with an idempotency guard enforced by the unique `orders.auction_id` constraint.

## References

[1]: https://www.postgresql.org/docs/current/ddl-constraints.html "PostgreSQL constraints documentation"
[2]: https://laravel.com/docs/13.x/eloquent-relationships "Laravel Eloquent relationships documentation"
