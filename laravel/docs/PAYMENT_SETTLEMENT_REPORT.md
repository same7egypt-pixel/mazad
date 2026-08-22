# Payment and Settlement Foundation

## Scope Implemented

The settlement foundation begins only after an auction close creates an order in the `waiting_payment` state. `InitiatePayment` locks that order, verifies that the authenticated buyer and marketplace country match, and creates one payment attempt in an `initiating` state. The configured country driver then creates the external checkout. The attempt becomes `pending` only after the driver returns its immutable external transaction identifier.

`PaymentGateway` is an explicit contract rather than a country-specific hard-code. Each real driver must verify its own signed raw webhook payload, emit a normalized `VerifiedPaymentWebhook`, and be registered against the corresponding country code in `marketplace.payment_gateways`. No live provider is configured yet because supported countries and commercial providers have not been selected; consequently, checkout initiation safely fails rather than routing money through an undeclared gateway.

| Domain operation | Transactional guard | Result |
| --- | --- | --- |
| Payment initiation | Locks the order and reuses an `initiating` or `pending` attempt | Prevents duplicate checkouts from concurrent buyer requests. |
| Webhook processing | Locks payment and order; validates driver, transaction id, amount, and currency | Moves a valid attempt to `succeeded`, `failed`, or `cancelled`; replaying a successful webhook is idempotent. |
| Seller settlement | Locks the seller wallet and applies a unique ledger reference | Creates one `sale_earning` pending entry after a successful payment. |
| Withdrawal request | Locks wallet, places a hold, encrypts destination details | Moves funds from available to pending and creates a `withdrawal_hold` ledger entry. |
| Finance review | Rechecks role and country scope under lock | Approves a request or restores the held balance with a `withdrawal_reversal` ledger entry. |

## API Surface

Authenticated buyers initiate a payment with `POST /api/orders/{order}/payments`. Wallet owners can read balances through `GET /api/wallets` and request a withdrawal through `POST /api/wallets/{wallet}/withdrawals`. Finance-authorized users review withdrawals through the approve and reject endpoints. The webhook route is `POST /api/payment-webhooks/{gateway}`; a provider driver must verify the request before `ProcessPaymentWebhook` applies any state change.

## Required Provider Activation

Before enabling payments in any country, add a production gateway driver that implements `PaymentGateway`, register its class only for the intended ISO country code, and store its credentials plus webhook secret in the deployment secret manager. The provider must map its verified callback to the internal payment ID and reject all invalid signatures before domain processing. The final release also needs the business policy that releases seller `pending_balance` to `available_balance` after the defined fulfilment or dispute period.

## Verification

PostgreSQL feature coverage verifies successful webhook replay does not duplicate the seller ledger credit, and verifies that finance rejection of a withdrawal restores the owner’s available balance. The implementation intentionally leaves real-network gateway certification and the payout execution connector for the country-specific integration phase.

## References

[1]: https://laravel.com/docs/13.x/queues "Laravel queue documentation"
[2]: https://laravel.com/docs/13.x/notifications "Laravel notification documentation"
