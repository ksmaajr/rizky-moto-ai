# AgentKit Codex transport override

This directory contains a narrowly scoped transport override for the upstream
`gpt_image25_agent` Python package.

The Laravel AgentKit provider prepends this directory to `PYTHONPATH`. The
local package uses `pkgutil.extend_path` so the installed upstream package
continues to provide its CLI, models, reference validation, receipts and file
helpers. Only `gpt_image25_agent.client` is overridden.

## Why this exists

The upstream client sends image requests to
`/backend-api/codex/responses` with a hosted `image_generation` tool and
`tool_choice`. The Codex backend rejects that request shape with HTTP 400.
This override instead uses the dedicated Codex Images endpoints:

- `/backend-api/codex/images/generations` when no reference images are supplied.
- `/backend-api/codex/images/edits` when reference images are supplied.

It keeps the selected image-model ID in the request and reads the final image
from `data[0].b64_json`. The existing Vercel provider and its code paths are
not involved in this override.

## Important

This is a compatibility transport patch, not an official OpenAI API client.
Codex's subscription backend is experimental and may not honor every requested
model or option. A successful live smoke test is still required to confirm the
specific account and model behavior. Do not use repeated live tests while an
account is rate-limited.
