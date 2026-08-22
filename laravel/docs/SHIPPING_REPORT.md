# Shipping Foundation

## Order Eligibility and Scope

A shipment is created only for an order in the `paid` state. The service locks the order and rejects a duplicate shipment, which preserves the one-to-one `orders` to `shipments` database contract. Every operational action checks the operator’s `shipping.manage` permission and requires the operator’s country to match the order country unless the operator has the global administrator role.

| Fulfilment type | Provider rule | Status route | Order effect |
| --- | --- | --- |
| `external` | Requires an active external provider in the same country | `pending → prepared → shipped → delivered` | Becomes `shipped`, then `completed` on delivery. |
| `internal` | No provider reference allowed | `pending → prepared → shipped → delivered` | Becomes `shipped`, then `completed` on delivery. |
| `self_pickup` | No provider reference allowed | `pending → prepared → ready_for_pickup → delivered` | Becomes `ready_for_pickup`, then `completed` on collection. |

External shipments must hold a tracking number before they move to `shipped`. Shipping addresses remain application-encrypted through the `Shipment` model cast. An already delivered shipment is idempotent: repeating `delivered` returns the current record without changing the completion timestamp a second time.

## API Surface

Operations users create a shipment with `POST /api/orders/{order}/shipments` and advance it with `POST /api/shipments/{shipment}/status`. Both routes require Sanctum authentication and the mandatory `X-Marketplace-Country` header. Existing `shipping_providers` records supply the country-scoped carrier catalog; credentials or carrier API settings remain encrypted and hidden from responses.

## Verification

PostgreSQL feature coverage verifies an external carrier must belong to the order country, verifies tracking before external dispatch, validates self-pickup’s distinct transition route, completes the order only at delivery, and rejects shipment creation by a user without operations permission. Carrier API booking, label purchase, and tracking webhooks remain country-provider integrations to implement after carrier selection.

## References

[1]: https://laravel.com/docs/13.x/authorization "Laravel authorization documentation"
[2]: https://laravel.com/docs/13.x/eloquent "Laravel Eloquent documentation"
