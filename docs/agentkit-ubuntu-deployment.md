# AgentKit on Ubuntu VPS

This deployment note covers the dedicated AgentKit queue worker and Codex CLI session placement. It does not claim that a browser-based OAuth flow has been implemented.

## Deployment model

- Laravel's regular queue worker and the dedicated `agentkit` queue are separate.
- On Ubuntu, configure `AGENT_AI_WORKER_DRIVER=supervisor`, `AGENT_AI_QUEUE=agentkit`, `AGENT_AI_WORKER_COUNT=3`, and `AGENT_AI_WORKER_SUPERVISOR_PROGRAM=rizky-moto-ai-agent`.
- Install the sample program from `deploy/supervisor/rizky-moto-ai-agent.conf` as `/etc/supervisor/conf.d/rizky-moto-ai-agent.conf`, after adjusting the application path and OS service account.
- Ensure the configured Laravel/PHP user can read the Python environment and AgentKit package, write to Laravel storage, and access the intended Codex home. Do not grant broad sudo permissions to PHP; if the dashboard must call supervisorctl, configure a narrowly scoped sudoers rule for this exact program and command only.
- Reload Supervisor after editing its configuration, then verify `supervisorctl status rizky-moto-ai-agent:*`. The dashboard's worker controls use this configured Supervisor program.

## Codex login belongs on the VPS

A Windows laptop's Codex session is not automatically available to Ubuntu. To use the account on the VPS, install the official Codex CLI on the VPS and complete login as the same dedicated OS user whose home directory the importer and AgentKit process will use. Where interactive browser access is not available, use the official Codex CLI device-auth flow if it is supported for the account and current CLI version.

Do not paste access tokens or the contents of `auth.json` into chat, logs, source control, or the browser UI. The current importer only supports a file-based `auth.json` layout with `auth_mode=chatgpt` and `tokens.access_token`. Codex can use secure OS storage instead; if so, the importer intentionally fails rather than guessing. This file format is not a supported stable token-export API.

## Current limitation: session lifecycle

The application currently encrypts the imported access token in Laravel and passes it to AgentKit as `CHATGPT_CODEX_ACCESS_TOKEN`. It does not yet implement an officially supported token-refresh exchange for that token. Therefore a successful import and smoke test do not guarantee the credential will remain usable indefinitely. Re-authentication/re-import may be needed if the token expires.

For production multi-user operation, do not share a single Codex home or one account's session across users. Each account requires OS-level isolation and a verified, supported credential acquisition/refresh lifecycle before it should be treated as production-ready.

## Deployment checklist

1. Deploy the branch and install PHP dependencies.
2. Configure the dedicated AgentKit queue and Supervisor program; do not stop the default Laravel queue.
3. Install AgentKit and its Python dependencies in the runtime used by PHP.
4. Install Codex CLI on the VPS and authenticate as the intended dedicated OS user.
5. Confirm the auth store is file-based before using `agent-ai:codex-import`; never print token contents.
6. Import into the matching Laravel user, then explicitly run the live `Test Token` check only when willing to spend one image request.
7. Verify worker status and logs before switching the active provider to Agent AI.
