# AgentKit Codex transport override

The Laravel AgentKit provider prepends this directory to `PYTHONPATH`.
`gpt_image25_agent/__init__.py` extends the installed upstream package path,
while this local `client.py` overrides only the upstream network transport.

## Native Codex Images endpoints

The override avoids the unsupported hosted `image_generation` tool selection
on the Responses endpoint. It posts text-to-image requests to
`/backend-api/codex/images/generations` and requests with reference images to
`/backend-api/codex/images/edits`, then validates and saves
`data[0].b64_json`. OAuth bearer credentials remain environment-provided;
the token is never logged. Account ID and residency headers are derived from
the token's JWT claims when present.

This route is experimental and must be validated against the application's
actual credential and account. A previous implementation received HTTP 403;
this version adds the Codex client identity, account context and image-turn
header used by current native Codex clients. If it still returns 403, preserve
the sanitized HTTP response for diagnosis rather than falling back silently
to the unsupported Responses tool.

The native endpoint does not currently support AgentKit mask inputs through
this adapter. Reference images are converted to data URLs and routed to the
edit endpoint. The Vercel provider is separate and is not changed by this
integration.
