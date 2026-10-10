# Codex Login → AgentKit Credential Bridge

Status: implementation gate / compatibility contract  
Branch: `feature/ai-provider-architecture`

## Current verified boundary

- The AgentKit CLI accepts a compatible access token through its documented token sources, including the `CHATGPT_CODEX_ACCESS_TOKEN` environment variable.
- The AgentKit CLI does not expose an interactive Codex OAuth login flow or a documented API for exchanging Codex's stored credentials for the token it expects.
- Codex CLI supports the official `codex login` flow and manages its own session credentials. Its local credential-store representation is an implementation detail unless a supported public interface explicitly guarantees otherwise.
- The Laravel model encrypts `AgentAiCredential.access_token` at rest, and the AgentKit provider passes that value to the child process as an environment variable.

## Current experimental bridge

The repository now includes `agent-ai:codex-import`. This is a deliberately version-sensitive adapter, not an official Codex token-export API:

1. On the same machine as the Laravel app, complete `codex login` in a terminal first.
2. Confirm Codex is using file-based auth storage. The importer supports the currently observed `auth.json` shape only: `auth_mode=chatgpt` and `tokens.access_token`.
3. Run `php artisan agent-ai:codex-import --user-id=ID_USER --name="Codex Account"`. To choose another file explicitly, pass `--auth-file="FULL_PATH_TO_AUTH_JSON"`. On Windows, use the actual path to the logged-in user's `.codex\auth.json`.
4. The command imports only the access token into Laravel's encrypted cast. It does not save the auth file, ID token, refresh token, or account ID. The credential starts as `pending_validation` and inactive.
5. In Settings, click `Test Token` for that pending credential only after deciding to spend one live image request. A successful AgentKit smoke test activates the credential; failure leaves it out of rotation.
6. Each account must use its own isolated Codex home and auth file before importing. Do not point multiple accounts at one shared default home.

This is experimental because Codex's local auth-file schema is an implementation detail and AgentKit has no auth-only endpoint. The importer intentionally fails closed when the expected fields are missing. Do not expose this command to untrusted users or run it on a multi-user server without OS-level account isolation. The web button does not itself open an interactive browser login; it explains the host-terminal steps.

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


## Agent AI runtime control center

The AI Provider settings page now exposes Start, Stop, Restart, and Refresh Status controls for the existing AgentKit worker manager. These controls manage the dedicated `agentkit` queue workers, not Laravel's default queue workers or the Vercel provider.

- Windows development uses the local worker driver and process/PID state under Laravel storage.
- Linux deployment is expected to use the configured Supervisor driver.
- The UI reads live driver status and shows running/healthy state, driver, platform, queue, and worker count.
- The worker manager is an existing queue-worker lifecycle controller. It is not yet an interactive browser OAuth controller and does not itself create or refresh ChatGPT sessions.

## Credential contract and remaining authentication gate

The currently implemented AgentKit invocation passes the encrypted `AgentAiCredential.access_token` as `CHATGPT_CODEX_ACCESS_TOKEN`. The generation worker retrieves that token through the credential pool for each invocation. This is the only credential material the current adapter explicitly passes to AgentKit.

Do not assume this access token is a durable full session or that it can be refreshed by AgentKit. The current experimental importer reads a file-based Codex `auth.json` shape; Codex may instead use OS secure storage, and its local format is not a stable public token-export API. A proper in-dashboard authentication worker still requires a supported login/token acquisition path that AgentKit can consume. Until that contract is proven, do not present the Add Account button as a completed browser-based login flow and do not activate an account before compatibility validation.


## Status for local testing

The runtime control center can be tested independently from authentication. The `Add Account` action is intentionally labeled as an experimental session bridge and does not claim to launch browser/device login yet. Do not interpret the presence of Codex CLI or a successful `codex login status` on a Windows developer laptop as proof that Laravel's web process or the Ubuntu VPS can access that same session.

Before production use, a dedicated authentication onboarding worker still needs to launch and supervise a supported login flow, report device/browser instructions and completion status without exposing secrets, and provide AgentKit with a credential lifecycle it can actually refresh. The current AgentKit interface only documents a compatible access-token input; the experimental backend and subscription access can change. See the upstream project documentation and review the applicable product terms before exposing this as a multi-user service.


## Importer hardening and automated checks

The importer now rejects unknown Laravel user IDs, symbolic links, unreadable files, and auth files larger than 1 MiB. It checks for an already-imported access token for the same Laravel user before creating a row. The token remains encrypted at rest and every new credential remains inactive until an explicit compatibility test succeeds.

The feature tests in `tests/Feature/ImportCodexCredentialCommandTest.php` cover:
- refusing a credential import for a non-existent Laravel user;
- encrypted persistence and the `pending_validation` state;
- rejecting duplicate imports for the same user.

Run the focused tests from the project root after dependencies and the testing database configuration are available:

```powershell
php artisan test --filter=ImportCodexCredentialCommandTest
```

These tests do not call the live AgentKit backend and do not spend image quota. They validate only the local importer contract. A live `Test Token` action is separate and can consume a real image request.

## Production readiness gate

This branch is not yet a fully automatic in-dashboard OAuth integration. The official Codex CLI does not provide a documented stable access-token export/refresh API for this bridge, and the AgentKit backend expects a compatible access token rather than owning the official login flow. Do not describe a successful import as a permanent login. If Codex moves its credentials to OS secure storage or changes the internal file schema, the importer fails closed. Token expiry can require re-authentication and re-import.

Before multi-user production, use an officially supported credential lifecycle or isolate a compatibility adapter per OS account and verify refresh/revocation behavior. Do not store raw auth files or refresh tokens in Laravel, and do not enable the AgentKit provider until the credential has passed explicit live validation.


## Windows local runtime setup

The repository includes `scripts/setup-agentkit-windows.ps1` for the first local runtime setup. Run it from the Laravel project root in PowerShell after Git, Python 3.10+, and the project's `.env` are present:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\setup-agentkit-windows.ps1
```

The script clones the upstream toolkit under `storage/app/agent-ai/agent-kit` (outside source control), creates a local virtual environment, installs the package, verifies `python -m gpt_image25_agent --help`, and configures the local `.env` to use that exact Python executable and the `agentkit` queue. It does not run `git pull` on an existing checkout, does not log into Codex, and does not call the live image backend. Review the script before running it and do not run it as Administrator unless your project setup specifically requires that.

After setup, clear Laravel's cached configuration if the application uses it. Runtime installation is separate from Codex authentication: the script does not create a credential, and a live credential test still consumes one image request.
