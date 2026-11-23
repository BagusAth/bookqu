# ADR-0023: Owner settlement is limited to payments without a provider link

> **Status:** Accepted
> **Date:** 2026-11-04
> **Decider:** Tech Lead
> **Work item:** BQ-087, BQ-049

## Context

ADR-0004 decision 6 lets the owner mark a booking paid or completed: the whole payment group is recorded `sukses` with `metode = cash`, and gateway cancellation of the open link is requested.

That decision assumes the open link can be closed. It cannot: SingaPay links cannot be cancelled at the gateway (ADR-0007), so the customer can still pay after the owner has settled.

The result is a double payment. The webhook then meets a payment that is already `sukses`, returns `AlreadyFinal`, and the second payment sits in the platform account with no refund and no alert (BQ-087 case 1).

The Tech Lead decided on 2026-11-01 to stop this (BQ-087). The decision is recorded for owners in `product-decisions.md` and in `owner-portal.md` §8.4, but ADR-0004 decision 6 was never amended, which blocked the work item.

## Options

1. **Keep owner settlement and rely on gateway cancellation.** Not possible while links cannot be cancelled.
2. **Keep owner settlement and refund any later provider success.** Allows a double charge by design, and every case needs a manual refund.
3. **Remove owner settlement for payments created with a provider link, and keep a safety net for a provider success on an already settled payment.** Chosen.

## Decision

1. **Provider-linked payments cannot be settled by the owner**, inside or after the payment window. A customer who pays on site after the window is recorded as a walk-in.
2. **While a payment awaits the provider** (pending and within its deadline plus the settlement margin, ADR-0009), the owner cannot mark the booking paid or completed and cannot cancel it. The rule reuses the existing hold window; it is not a second deadline calculation.
3. **After the window,** an owner cancelling a still-pending booking first expires its payment through the Payment lifecycle, then cancels the group, in one transaction.
4. **Safety net.** A provider success for a payment already settled by the owner records one refund owed with the reason `double-payment`, logs a critical event and notifies the owner, like a late success (BR-PAY-001). This covers rows settled before this decision and webhooks that arrive late. A repeated webhook creates no second refund (INV-PAY-06), and a repeated webhook of a normal online success stays `AlreadyFinal` (INV-PAY-03).
5. **`PaymentTransition::SettledByOwner` remains** for payments without a provider link. Walk-in and free bookings are settled at creation and are unaffected.
6. **Amends ADR-0004 decision 6.** All other decisions of ADR-0004 stand.

## Consequences

- An owner-visible action disappears for online payments; the owner sees that the booking is waiting for online payment, and until when.
- BQ-087 implements this ADR and closes BQ-049.
- A refund created here follows the processing of ADR-0020.
- Tests are listed in BQ-087, including the MySQL race between an owner action and a webhook.
