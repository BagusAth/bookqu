---
id: BQ-021
title: Production mail, error alerting and environment checklist
type: ops
status: proposed
priority: P1
phase: 4
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

_Fill in when done._
