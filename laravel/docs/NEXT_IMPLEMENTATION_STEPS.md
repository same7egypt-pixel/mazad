# Next Implementation Steps

The completed foundation deliberately stops before the marketplace UI and before any externally executed payment, carrier, SMS, or WhatsApp integrations. The next implementation should proceed in the following sequence.

| Priority | Deliverable | Critical acceptance criteria |
|---|---|---|
| 1 | Country and catalog services | Country context is mandatory; city/category selection is validated against the selected country; products follow draft, review, approval, active, sold, and rejection transitions. |
| 2 | Media upload pipeline | Form requests enforce MIME/size limits; uploads use private S3-compatible objects; authorization is checked before signed-download access. |
| 3 | Auction aggregate and bid service | PostgreSQL row lock, database transaction, increment/reserve/time checks, self-bid prevention, idempotency strategy, bid history, after-commit event, and concurrency tests. |
| 4 | Lifecycle jobs and broadcasts | Scheduled start/close jobs are idempotent; a successful close selects a winner and creates one order; Reverb broadcasts only committed results. |
| 5 | Orders, payment abstraction, and wallet ledger | Payment gateways are selected by country; webhook signatures are verified; commissions are immutable; wallet entries form an auditable double-entry-style ledger. |
| 6 | Shipping, reviews, notifications, and search | Reviews require completed orders; shipping statuses are transition-validated; Scout indexes only approved/active listings and honors filters. |
| 7 | Admin Control Tower | Install and configure Filament after operational policies and scope queries are in place; country administrators see only their permitted country records. |
| 8 | Release hardening | Add feature, integration, queue, event, and concurrent-bid tests; run database query analysis; enable production secret management, backups, monitoring, alerting, and a formal security review. |

> The atomic bid service is the highest-risk domain component. It should be implemented and load-tested before enabling any public listing or payment flow.

## References

[1]: https://www.postgresql.org/docs/current/explicit-locking.html "PostgreSQL explicit locking documentation"
[2]: https://laravel.com/docs/13.x/queues "Laravel queues documentation"
[3]: https://laravel.com/docs/13.x/events "Laravel events documentation"

