---
id: BQ-021
title: Production mail, error alerting and environment checklist
type: ops
status: in-progress
priority: P1
phase: 5
domain: —
requirements: [NFR-OBS-004, NFR-OBS-005]
invariants: []
launch-blocker: true
needs-lead-approval: true
depends-on: []
---

# BQ-021: Production mail, error alerting and environment checklist

## Problem

`.env.example` uses `MAIL_MAILER=log`; there is no error alerting; the production `.env` is not verified against the launch checklist.

## Plan

Configure a transactional mail provider and an alert channel (for example mail on `critical` logs, or a monitoring service — a new dependency needs approval); verify the production `.env` against `docs/operations.md` §2.

## Evidence

2026-11-01, plan approved by the Tech Lead: production env checklist, opt-in cached trusted-proxy configuration and scheduler health checks implemented/documented; release notes prepared.
Waiting for the Tech Lead: production SMTP + queued test email, config cache rebuild, UptimeRobot `/up` every five minutes, payment live setup/smoke test, and operational alert verification. No production secrets were read or changed.
