# Discovery and Trust Foundation

## Listing Search

`GET /api/listings/search` is a country-scoped discovery endpoint. It requires the standard `X-Marketplace-Country` header and always constrains products to that active country and `active` product state. It accepts optional text, city, category, condition, minimum price, and maximum price filters. Price filtering uses the current price of a live auction, so inactive and closed inventory cannot satisfy an auction-price request.

Laravel Scout configuration is now explicit. Active products expose a minimal index document containing country, city, category, currency, title, description, condition, and status. Meilisearch is configured for production-style filtering while the database driver remains the safe default for environments without a running search service. The canonical API still applies relational access filtering before it returns records.

## Reviews

Review submission is available only to a buyer or seller of the same-country order after that order reaches `completed`. The review service rejects non-participants, unverified permissions, cross-country attempts, unfinished orders, and a second review from the same reviewer for the same order. PostgreSQL enforces the final rule with the unique `(reviewer_id, order_id)` constraint.

> The repository deliberately contains no seeded, mocked, or hard-coded customer reviews, ratings, or testimonials. Eligibility coverage checks access conditions without creating user-generated review content.

## Notification Inbox

Authenticated users can list their own database notifications and mark one of their own notifications as read. Both operations filter by the current marketplace country stored in notification data. The review service queues an after-commit database notification for the reviewed counterparty; no notification is sent unless a real review is created at runtime.

## Verification

PostgreSQL tests cover country-constrained listing search and active price filters, country-isolated notification listing and read transitions, and non-content review eligibility checks. Meilisearch runtime indexing remains to be verified when the Docker Compose stack is available.

## References

[1]: https://laravel.com/docs/13.x/scout "Laravel Scout documentation"
[2]: https://laravel.com/docs/13.x/notifications "Laravel notifications documentation"
