# Architecture Report — Auction Marketplace Foundation

**Author:** Manus AI  
**Scope:** Backend foundation only; no storefront or admin user interface has been implemented in this delivery.

## Overview

The project is a standalone **Laravel 13 / PHP 8.3** modular-monolith foundation located in the `laravel/` directory. Its local development environment is defined through Docker Compose and separates the HTTP application, queue worker, WebSocket server, scheduler, PostgreSQL database, Redis, Meilisearch, and MinIO-compatible object storage into explicit services. This separation keeps application responsibilities clear while retaining a single deployable codebase.

| Concern | Implementation | Responsibility |
|---|---|---|
| HTTP application | PHP-FPM plus Nginx | Serves Laravel and API traffic on `http://localhost:8000`. |
| Data | PostgreSQL 16 | Stores transactional marketplace, authentication, audit, and RBAC data. |
| Queue and cache | Redis 7 plus Horizon | Provides Redis-backed queue processing, cache, and Horizon supervision. |
| Real-time | Laravel Reverb | Provides the WebSocket process required for live auction broadcasts. |
| Search | Meilisearch | Receives queued Scout indexes for product discovery. |
| Object storage | MinIO | Supplies a local S3-compatible private `auction-media` bucket. |
| Scheduled work | Laravel scheduler process | Hosts scheduled auction lifecycle commands when they are implemented. |

> **Operational boundary:** the Compose file starts infrastructure and application processes, but it deliberately does not run migrations automatically. Database changes remain an explicit, reviewable deployment operation.

## Modular Monolith Boundary

The `Modules/` directory contains scaffolds for Core, Authentication, Users, Countries, Cities, Currencies, Categories, Products, Auctions, Bids, Orders, Payments, Wallet, Shipping, Reviews, Notifications, Support, Analytics, and Admin. These scaffolds are **disabled** in `modules_statuses.json` until their route handlers and policies are implemented. This prevents generated placeholder resource routes from being exposed before they have real authorization and business behavior.

Shared foundations presently live in `app/` to avoid premature cross-module dependencies. The domain modules should receive their controllers, form requests, services, jobs, events, listeners, policies, and module migrations as each bounded context is implemented.

## Security and Context Controls

Every marketplace aggregate carries `country_id`, and commerce records also carry currency information where money is involved. `MarketplaceContext` and the `marketplace.country` middleware require an active `X-Marketplace-Country` request header, select the matching active country, and reject access to a different country. Product and auction policies further prevent cross-country control and seller self-bidding.

Sanctum token authentication, verified-user eligibility checks, account status checks, request validation, application-level encrypted fields, rate limits for auth and bids, Spatie RBAC, Spatie activity logging, an immutable audit-log table, and initial device/activity tracking are included as foundations. Laravel Horizon access is restricted to `GLOBAL_SUPER_ADMIN` in non-local environments.

## Real-Time and Atomic Bidding Design

The stack contains the required Reverb, Redis, queue, and scheduler configuration. The next auction implementation must place bids in one PostgreSQL transaction using `SELECT ... FOR UPDATE` (Laravel `lockForUpdate`) on the auction row, verify status/time/country/seller/price/increment, insert the bid, update `current_price`, `winner_id`, `bid_count`, and optimistic `version`, commit, then broadcast an event through the queue. This ordering is essential: broadcast only after a successful commit, and never rely on a browser-side price for authorization.

## References

[1]: https://laravel.com/docs/13.x/sanctum "Laravel Sanctum documentation"
[2]: https://laravel.com/docs/13.x/horizon "Laravel Horizon documentation"
[3]: https://laravel.com/docs/13.x/reverb "Laravel Reverb documentation"
[4]: https://laravel.com/docs/13.x/scout "Laravel Scout documentation"
