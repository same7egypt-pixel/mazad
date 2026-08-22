# Next Implementation Steps

The completed foundation deliberately stops before the marketplace UI and before any externally executed payment, carrier, SMS, or WhatsApp integrations. The next implementation should proceed in the following sequence.

| Priority | Deliverable | Critical acceptance criteria |
|---|---|---|
| 1 | Country and catalog services | Country context is mandatory; city/category selection is validated against the selected country; products follow draft, review, approval, active, sold, and rejection transitions. |
| 2 | Media upload pipeline | Form requests enforce MIME/size limits; uploads use private S3-compatible objects; authorization is checked before signed-download access. |
| 3 | Auction aggregate and bid service | **Completed foundation:** approved-product auction creation, country/currency checks, PostgreSQL row locks, transactions, increment/reserve/time checks, self-bid prevention, bid history, and post-commit broadcast dispatch are implemented. A true parallel-worker contention test remains for the hardening phase. |
| 4 | Lifecycle jobs and broadcasts | **Completed foundation:** scheduled start/close commands are idempotent, products transition with auction state, eligible sellers can cancel unstarted auctions, a successful close creates one order, and broadcasts/notifications are queued after commitment. |
| 5 | Orders, payment abstraction, and wallet ledger | Payment gateways are selected by country; webhook signatures are verified; commissions are immutable; wallet entries form an auditable double-entry-style ledger. |
| 6 | Shipping, reviews, notifications, and search | Reviews require completed orders; shipping statuses are transition-validated; Scout indexes only approved/active listings and honors filters. |
| 7 | Admin Control Tower | Install and configure Filament after operational policies and scope queries are in place; country administrators see only their permitted country records. |
| 8 | Release hardening | Add feature, integration, queue, event, and concurrent-bid tests; run database query analysis; enable production secret management, backups, monitoring, alerting, and a formal security review. |

> The atomic bid service is the highest-risk domain component. The lock-based implementation and lifecycle integration are present, but it still requires a parallel-worker contention test and production load test before public launch.

## References

[1]: https://www.postgresql.org/docs/current/explicit-locking.html "PostgreSQL explicit locking documentation"
[2]: https://laravel.com/docs/13.x/queues "Laravel queues documentation"
[3]: https://laravel.com/docs/13.x/events "Laravel events documentation"
