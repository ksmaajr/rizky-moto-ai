# AgentKit AI Modernization Plan

Status: Planning baseline — implementation must be verified incrementally.
Target repository: ksmaajr/rizky-moto-ai
Working branch: feature/ai-provider-architecture

## Goals

Modernize the AgentKit integration without regressing the existing Vercel provider, image-generation workflow, generation-history card layout, or generator loading animation. Develop and test locally on Windows first, then deploy to AWS Ubuntu only after validation.

## Agreed architecture

- Keep AgentKit behind the existing provider abstraction so Vercel and AgentKit remain independently selectable.
- Keep the shared AgentKit credential pool and dedicated agentkit queue.
- Keep three AgentKit workers sharing the same account pool. Worker lifecycle management remains separate from credential management.
- Keep Queue Workers and AgentKit Workers as separate collapsible sections inside AI Engine.
- Keep worker start/stop/restart behavior compatible with both local Windows and AWS Ubuntu.
- Use encrypted credential storage; never display raw access tokens, refresh tokens, passwords, or session cookies in the UI or logs.
- Verify the actual authentication flow and backend compatibility before implementing refresh-token automation. Do not assume official OAuth tokens are interchangeable with the experimental AgentKit backend.
- Use shared locking/atomic persistence to prevent concurrent refreshes and refresh-token rotation races.
- Treat rate limits, expired credentials, revoked sessions, network failures, and provider errors as distinct states.
- Use bounded retries and account failover. Avoid duplicate generation attempts when the upstream request outcome is ambiguous.
- If all accounts are unavailable, keep jobs in a clear retryable/blocked state and surface actionable UI feedback rather than retrying forever.

## Session recovery lifecycle

1. Detect and classify authentication/provider errors.
2. If a supported refresh mechanism is available, refresh before expiry where possible.
3. Persist replacement credentials atomically and verify the account before returning it to the healthy pool.
4. If refresh fails permanently or the session is revoked, mark the account as requiring re-login.
5. Fail over to another healthy account when available, without restarting all workers.
6. Provide a dashboard re-login flow and a post-login connection test.
7. Never mark an account invalid solely because of a transient network/server error.

## Per-account usage and health monitoring

Use two explicitly separated data sources:

- Provider/Codex usage data, only where a compatible, supported source actually exposes it: quota/usage, reset time, session/account status, and provider limits.
- Laravel/worker telemetry: requests, success/failure counts, latency, last request/check time, error categories, cooldowns, refresh attempts, and account failovers.

Normalize source data through an adapter and store historical snapshots for charts. Do not fabricate quota values or reset times. Show Unavailable when upstream usage data cannot be verified. Keep provider-sourced metrics clearly distinguishable from locally computed telemetry.

Health classification should consider authentication validity, recent request outcomes, rate-limit state, refresh state, and data freshness. Avoid claiming a numeric health score unless its calculation is transparent and grounded in recorded signals.

## Agreed UI/UX

### AI Provider > AI Configuration
- Keep the main configuration page concise and uncluttered.
- Show compact account cards with account label, health/session status, last check, and only a few useful summary metrics.
- Provide distinct Monitoring and Manage account actions.
- Monitoring opens a per-account detail drawer on desktop and a full-screen sheet/page on mobile; do not render every chart on the main configuration page.
- The monitoring detail should include overview, usage/quota when available, request history, authentication/refresh state, rate-limit/cooldown history, and health timeline.
- Account management should support add/login, connection test, re-login, and confirmed removal.
- Make all layouts responsive with no horizontal overflow.

### Motion and accessibility
- Use subtle, purposeful transitions (typically 150–300 ms), smooth drawer/sheet entry, restrained status transitions, and lightweight live updates.
- Avoid flashing, layout shifts, excessive animation, or rerendering the whole page for a status change.
- Load detailed charts only when monitoring opens.
- Respect prefers-reduced-motion, keyboard navigation, Escape-to-close, and accessible focus handling.

### Locked UI / regression boundaries
- Do not unintentionally change the existing image generator layout.
- Preserve the generation-history card layout and generator loading animation already agreed to be locked.
- Preserve Vercel provider behavior.
- Keep Queue Workers and AgentKit Workers separate and collapsible within AI Engine.
- Do not combine credential-pool controls with worker lifecycle controls.

## Implementation order

### Phase 0 — Audit and baseline (first)
- Inspect current branch, migrations, models, credential encryption, provider adapter, queue/job flow, worker manager, Livewire components, routes, and tests.
- Record current behavior and identify existing implementations before changing anything.
- Verify local Windows commands and Linux/AWS driver boundaries.
- Capture baseline test results and identify missing migrations or untracked work.

### Phase 1 — Stabilize provider and credential contracts
- Confirm credential schema and secret encryption.
- Confirm provider contract, token selection, and shared account state.
- Add focused tests for token selection, concurrency/locking, error classification, and failover.
- Keep this phase backend-first; avoid unrelated generator UI changes.

### Phase 2 — Authentication recovery
- Prove which login/refresh mechanism is supported by the current AgentKit version.
- Implement refresh only if compatible and permitted.
- Add re-login-required state and a safe dashboard re-authentication flow.
- Verify atomic refresh-token rotation and behavior across all three workers.

### Phase 3 — Telemetry and usage adapter
- Implement worker-side telemetry first.
- Inspect the actual AgentKit/Codex interfaces for supported usage data before wiring upstream usage.
- Add normalized usage snapshots and history; unavailable provider metrics remain explicitly unavailable.
- Add account health classification and cooldown/failover signals.

### Phase 4 — Monitoring API and UI
- Keep AI Configuration compact.
- Add account cards and per-account monitoring drawer/full-screen mobile view.
- Add loading, empty, stale-data, error, re-login, and reduced-motion states.
- Preserve the locked generator UI.

### Phase 5 — Validation and deployment readiness
- Run relevant Laravel tests, static checks, migration checks, and manual local tests.
- Test expired/invalid token, rate limit, transient server failure, refresh race, all accounts unavailable, and successful failover.
- Test responsive UI and reduced-motion behavior.
- Test Windows worker lifecycle, then Linux/AWS Supervisor behavior.
- Deploy only after local validation and explicit review.

## Acceptance criteria

- Three workers can share the pool without concurrently refreshing the same account.
- Expired sessions recover automatically only through a verified compatible refresh mechanism; otherwise the UI requests re-login.
- One bad account does not stop healthy accounts or all workers.
- Usage data is accurately sourced and labeled; unknown quota/reset data is never invented.
- Per-account monitoring opens on demand and does not clutter AI Configuration.
- The main generator, Vercel provider, history cards, loading animation, and worker controls have no regressions.
- Windows local development and AWS Ubuntu deployment remain supported.
