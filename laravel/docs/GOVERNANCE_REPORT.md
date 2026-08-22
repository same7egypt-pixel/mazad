# Governance and Activity Foundation

## Recorded Events

After a successful bid commits, the system records an `auction.bid_placed` user-activity event and a corresponding audit-log entry. Both records carry the auction country and actor, so operational teams can investigate a marketplace without querying or exposing data from another country.

The first implemented signal is `fraud.suspicious_bid_velocity`. It is a **review trigger, not a fraud verdict**: the event is created when a bidder reaches five bids in the same country within five minutes. The record preserves the signal window, bid count, auction identifier, and bid identifier for an authorized reviewer.

| Endpoint | Permission | Scope |
| --- | --- | --- |
| `GET /api/governance/fraud-signals` | `fraud.review` | Current marketplace country; global administrators may span assigned context. |
| `GET /api/governance/audit-logs` | `audit.view` | Current marketplace country; actor information is eager-loaded. |

## Operational Boundary

These endpoints are review-only. They deliberately do not suspend users, reverse bids, or make automated enforcement decisions. Any future action workflow should require a separate human-review policy, auditable resolution state, and a documented appeal process.

## Verification

PostgreSQL coverage verifies that bid velocity produces a reviewable signal and audit record, and that a reviewer from another country is refused access even when they hold the `fraud.review` permission.

## References

[1]: https://laravel.com/docs/13.x/authorization "Laravel authorization documentation"
[2]: https://laravel.com/docs/13.x/eloquent "Laravel Eloquent documentation"
