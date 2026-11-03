# ADR-0020: Refund requests, owner decisions and admin processing

> **Status:** Proposed
> **Date:** 2026-11-04
> **Decider:** Tech Lead — Product Lead for the product scope (D-001)
> **Work item:** BQ-126 (specification); implementation items follow in epic E3

## Context

**D-001 sets the refund policy:**
- refunds are optional per tenant;
- a customer requests a refund;
- the owner decides 100%, 50% or rejects;
- a business cancellation of a booking paid online is always a full refund;
- the platform admin transfers the money, and the tenant balance is debited (FR-REFUND-001 to FR-REFUND-007, FR-ADMIN-005).

**What exists today (ADR-0004 decision 7):**
- the Payment lifecycle creates at most one `Refund` per payment, recorded `pending` and returned manually;
- `CancelBooking` creates a refund when a customer cancels a paid booking;
- an owner cancellation creates none;
- `TenantBalanceLedger` debits a refund of a credited payment;
- no admin refund screen exists.

## Options

1. **Keep refunds as a side effect of cancellation and add a percentage field.** It cannot hold a request that waits for a decision while the slot stays held (FR-REFUND-003).
2. **A refund request record, decided by the owner, that leads to a cancellation and a refund through the existing modules.** Chosen.

## Decision

1. **Refund request.** A new table `refund_requests`:

   | Column | Meaning |
   |---|---|
   | `idtenant` | The tenant |
   | `idpayment` | The payment group |
   | `reason` | The customer's reason |
   | `bank_nama`, `nomor_rekening`, `nama_pemilik_rekening` | Bank account details, encrypted casts |
   | `status` | `open`, `accepted_full`, `accepted_half` or `rejected` |
   | `decided_by` | The deciding user |
   | `decision_reason` | Required when the request is rejected |
   | `decided_at` | When it was decided |

   - At most one `open` request per payment group.
   - The state rules live in `App\Domain\Payment\RefundRequestState`.
2. **Customer request.** The customer's cancel operation for a paid booking becomes a request (FR-BOOKING-013, FR-REFUND-002). It needs:
   - a Management access grant with the cancellation operation (ADR-0006);
   - refunds turned on in the booking policy;
   - the request to be made before the deadline (`cancel_before_hours`, FR-TENANT-008).

   Nothing changes on the booking or the payment, and the slot stays occupied.
3. **Owner decision** (`DecideRefundRequest`, ability `refunds.decide`, ADR-0016) runs in one tenant-first transaction:
   - **Accept:** cancels the whole payment group through `CancelBooking`, and asks the Payment lifecycle for a refund of the decided amount. The amount is 100% of the paid total, or 50% rounded down to whole rupiah.
   - **Reject:** keeps the booking.

   The customer is notified after commit.
4. **Business cancellation.** When the business cancels a booking paid online (Pemilik or Manajer), `CancelBooking` always requests a full refund and requires a reason. A Kasir cannot cancel: the Kasir creates a cancellation request (FR-TEAM-006) that a Pemilik or Manajer decides.
5. **Refunds are still created only by the Payment lifecycle**, at most one per payment (ADR-0004 decision 7).
   - Its refund request carries the amount and the cause: `customer_request`, `business_cancel` or `late_success`.
   - The cancellation path no longer creates a refund for a customer cancellation without a decision.
6. **Balance.** `TenantBalanceLedger` debits the refund amount when the refund is created, as today. The balance may go below zero; the admin settles that outside the app (existing v1 decision).
7. **Admin processing** (FR-ADMIN-005):
   - the admin marks a `pending` refund `processed` with a transfer reference, or `failed` with a reason;
   - a failed refund keeps the balance debit and stays visible for a retry;
   - the customer and the owner are notified after commit.
8. **Undecided requests.** No job decides money. A scheduled reminder notifies the Pemilik and Manajer of open requests, and the request remains open after the session (FR-REFUND-005).
9. **Secrets and logs.** Bank account numbers are stored encrypted, shown masked to the admin until the transfer, and never logged in full.

## Consequences

- **Amends ADR-0004 decision 7:** refunds carry an amount and a cause, and the customer cancellation path goes through the request.
- **Changes the requirement text** of the old "Refund Rules" (`requirements.md`).
- **Tests:**
  - request before and after the deadline, with refunds on and off;
  - one open request per group;
  - 100% and 50% amounts, including rounding;
  - rejection keeps the slot;
  - business cancellation always refunds in full;
  - Kasir refusal;
  - the admin transitions;
  - the balance debit;
  - idempotency of a repeated decision;
  - tenant isolation of requests.
- **Docs:** `.ai/rules/payment.md` references this ADR. `docs/domains/payment.md`, `booking.md` and `balance-payout.md` change with the implementation.
