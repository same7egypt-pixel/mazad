# Local Development Running Instructions

## Prerequisites

Install Docker Engine with the Docker Compose plugin on the local development machine. No host PHP, Composer, PostgreSQL, Redis, Meilisearch, or MinIO installation is required for normal application development because the Compose stack provides them.

## First Start

From the `laravel/` directory, create a local environment file from the supplied non-secret template, start the services, generate the application key, migrate, and seed roles and permissions.

```bash
cp docker/local.env.template .env
docker compose up -d --build
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --seed
```

The application is available at `http://localhost:8000`. Reverb listens on port `8080`; Meilisearch listens on `7700`; MinIO's API and console listen on `9000` and `9001`; PostgreSQL and Redis are also forwarded to their standard local ports by default.

| Service | Compose service | Local endpoint |
|---|---|---|
| Laravel HTTP | `web` | `http://localhost:8000` |
| Reverb WebSocket server | `reverb` | `ws://localhost:8080` |
| PostgreSQL | `postgres` | `localhost:5432` |
| Redis | `redis` | `localhost:6379` |
| Meilisearch | `meilisearch` | `http://localhost:7700` |
| MinIO console | `minio` | `http://localhost:9001` |

## Everyday Commands

```bash
make logs          # Tail all service logs
make shell         # Open a shell in the PHP application container
make migrate       # Run pending migrations
make seed          # Seed roles and permissions
make test          # Run Laravel tests in the container
make down          # Stop the local stack
```

## Auction Lifecycle Verification

The lifecycle suite uses PostgreSQL rather than SQLite because the auction services depend on row-level locks and transaction semantics. In the Compose environment, create a dedicated disposable test database and role matching the non-production `phpunit.xml` configuration, then run:

```bash
docker compose exec app php artisan test tests/Feature/AuctionLifecycleTest.php
```

The suite covers approved-product scheduling, state transitions, cancellation, idempotent order creation, minimum-increment validation, and queued bid broadcasting. It deliberately does not replace the pending multi-process bid-contention test.

The local template contains development-only credentials and **must not** be copied unchanged to a shared, staging, or production environment. Set strong unique database, MinIO, Meilisearch, Reverb, and application-key values through the target deployment's secret manager. The project keeps the Compose services separate so production may replace them with managed equivalents without changing the application contracts.

## Local API Foundation

All active API calls require the `X-Marketplace-Country` header. Registration and login are exposed at `POST /api/auth/register` and `POST /api/auth/login`. The registration flow expects the selected country identifier to match that header and validates that the chosen city belongs to the same active country. Public auction reads are available through `GET /api/auctions`, `GET /api/auctions/{auction}`, and `GET /api/auctions/{auction}/bids`; authenticated sellers use `POST /api/auctions` and `POST /api/auctions/{auction}/cancel`.

## References

[1]: https://docs.docker.com/compose/ "Docker Compose documentation"
[2]: https://laravel.com/docs/13.x/configuration "Laravel configuration documentation"
