# Codex Login → AgentKit Credential Bridge

Status: implementation gate / compatibility contract  
Branch: `feature/ai-provider-architecture`

## Current verified boundary

- The AgentKit CLI accepts a compatible access token through its documented token sources, including the `CHATGPT_CODEX_ACCESS_TOKEN` environment variable.
- The AgentKit CLI does not expose an interactive Codex OAuth login flow or a documented API for exchanging Codex's stored credentials for the token it expects.
- Codex CLI supports the official `codex login` flow and manages its own session credentials. Its local credential-store representation is an implementation detail unless a supported public interface explicitly guarantees otherwise.
- The Laravel model encrypts `AgentAiCredential.access_token` at rest, and the AgentKit provider passes that value to the child process as an environment variable.

## Required bridge behavior

1. Start the official Codex sign-in flow on the same host/environment that owns the account session.
2. Keep each connected account isolated; a single shared default Codex home cannot safely represent multiple independent accounts.
3. Obtain only the credential material that AgentKit actually accepts, through a supported interface or a deliberately isolated compatibility adapter.
4. Validate token shape and provenance without logging or displaying secrets.
5. Verify AgentKit compatibility before activating the account. The current AgentKit CLI has no auth-only endpoint, so its live image smoke test consumes a real request and must only run after explicit user confirmation.
6. Persist only the required token using Laravel encrypted casts; never persist the complete Codex auth file, cookies, or refresh tokens by default.
7. Treat expiry, revoked sessions, rate limits, transient upstream errors, and unsupported credential formats as distinct statuses.
8. If no stable supported extraction interface exists, label the bridge as experimental and fail closed rather than silently assuming a token format.

## Security and runtime constraints

- Do not expose an arbitrary shell command, token file path, or raw token input as the normal account onboarding UX.
- Do not read a browser's local Codex session from a remote AWS server. The login and credential adapter must run in the environment where the user authorizes the account.
- Do not put access tokens, refresh tokens, auth-file contents, or subprocess environment dumps in logs or Livewire state.
- Keep account management separate from AgentKit worker lifecycle controls.
- Keep the Vercel provider, image generator layout, generation history, and loading animation unchanged.

## Acceptance criteria before enabling Add Account

- A user-initiated official login completes without requiring a pasted token.
- The bridge can retrieve the exact token type accepted by the installed AgentKit version using a documented or explicitly version-pinned compatibility path.
- The account is not marked active until compatibility is verified.
- Multiple accounts remain isolated and three workers can share the encrypted pool safely.
- A failed login or unsupported token format leaves existing accounts and workers unaffected.
- The user explicitly confirms any live image smoke test that can consume quota.
