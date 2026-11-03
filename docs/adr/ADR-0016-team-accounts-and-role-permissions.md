# ADR-0016: Team accounts and role permissions

> **Status:** Accepted
> **Date:** 2026-11-04
> **Decider:** Tech Lead (accepted 2026-11-04) — Product Lead for the product scope (D-011, D-017)
> **Work item:** BQ-126 (specification); implementation items follow in epic E5

## Context

- **D-011** turns "staff" into team members who sign in to one business as **Pemilik**, **Manajer** or **Kasir**. FR-TEAM-001 to FR-TEAM-006 and `owner-portal.md` §5 define what each role may do. **D-017** limits the number of team accounts per plan (Small 0, Medium 2, Pro 10).
- **How access works today:**
  - one `users` row with `role = owner` owns one tenant through `tenants.iduser`;
  - `TenantMiddleware` sets `TenantContext` from the authenticated owner;
  - Policies check that a record belongs to that tenant (ADR-0010).
- **Gaps:**
  - no concept of a second user inside a tenant;
  - no permission below "owner of the tenant";
  - no record of who performed an action.
- **The old `staff` table** holds named service providers (no login) and is withdrawn (FR-STAFF-001).

Invariants at stake: INV-TEN-01 (no cross-tenant access), INV-TEN-02 (tenant context only from the middleware).

## Options

1. **Team members as extra `users` rows with `role = owner`, linked by `tenants.iduser`.** Impossible: a tenant has one `iduser`.
2. **A role column on `users` plus a tenant id on `users`.** Simple, but mixes the platform role (`owner`, `admin`) with the tenant role, and blocks a user from ever belonging to another tenant.
3. **A membership table between users and tenants, with the tenant role on the membership, and one permission map in the domain.** Chosen.

## Decision

1. **Membership.** A new table `tenant_members`:
   - columns: `idtenant`, `iduser`, `role` (`pemilik`, `manajer`, `kasir`), `invited_by`, `invited_at`, `accepted_at`, `revoked_at`;
   - unique on (`idtenant`, `iduser`);
   - a migration creates the `pemilik` membership for every existing tenant owner;
   - `tenants.iduser` stays as the owning account and must equal the single `pemilik` member.
2. **Platform role stays platform role.** `users.role` keeps `owner` (any tenant user) and `admin`. Team members are `users` with `role = owner` and a non-`pemilik` membership. Admin authorization is unchanged (ADR-0010 decision 1).
3. **Tenant context from the active membership.**
   - `TenantMiddleware` resolves the tenant of an authenticated tenant user from their accepted, non-revoked membership. In this release a user belongs to at most one tenant.
   - The membership is re-read on every request, so a revoked member is refused at the next request (FR-TEAM-001).
   - Nothing reads a tenant or role from request input (INV-TEN-02).
4. **One permission map.** `App\Domain\Team\TenantPermission` is the only home of FR-TEAM-002:
   - it maps each role to named abilities (for example `bookings.cancel`, `refunds.decide`, `payouts.manage`, `team.manage`);
   - Policies and a `can:` route middleware ask it;
   - no controller, view or middleware compares role names;
   - navigation reads the same map (ADR-0021).
5. **Policies check tenant and ability.**
   - Every owner Policy (ADR-0010 decision 2) checks that the record's tenant is the user's tenant and that the user's role has the ability.
   - A missing ability is a 403 for actions and the "Tidak punya akses" page for pages.
   - A foreign id stays a 404.
6. **Invitation.**
   - The Pemilik invites by email with a role.
   - The invitation is a single-use, expiring secret, stored as a hash (the ADR-0006 pattern), and is accepted by creating or signing in to an account with the invited email.
   - Seat limits come only from `EntitlementRules` (INV-SUB-01), checked under the tenant lock when the invitation is created and when it is accepted.
7. **Activity attribution.**
   - Every Action that creates, changes or transitions a tenant record receives the acting user and writes the actor (`iduser`, role) to the booking log for booking events, and to a new `tenant_activity_logs` table for all others.
   - Actions get the actor from the authenticated user that the controller passes; they never trust a request field.
   - Logs never contain passwords, tokens or bank account numbers in full.
8. **Revenue visibility is a permission too.** Hiding revenue figures for Kasir (FR-TEAM-002) is the ability `revenue.view`. Controllers omit the figures from the view data; the view does not hide them by itself.
9. **The old staff module is removed.**
   - The `staff` routes, views and the `service_staff` link are deleted.
   - The `staff` table is kept read-only for one release and then dropped.
   - Staff rows are not converted into members, because a member needs a real account.

## Consequences

- Every owner controller and Policy gains an ability check; the `TenantPermission` map plus a test per role (allowed and refused) proves FR-TEAM-002.
- `OwnerSidebarNavigationTest` gains one case per role.
- Session handling must re-validate the membership. Tests cover revocation during a session and an invitation accepted after the seats are full.
- Tests prove that a member of tenant A cannot reach tenant B (INV-TEN-01).
- `AGENTS.md` rule 3 and `.ai/rules/tenancy-security.md` reference this ADR.
- `docs/domains/auth.md` and `docs/domains/tenancy.md` change when the work item lands, not before.
