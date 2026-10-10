# AgentKit upstream package path

This directory is only a Python package-path bridge for the installed upstream
`gpt-image-2-5-agent-kit` distribution.

The Laravel AgentKit provider prepends this directory to `PYTHONPATH`.
`gpt_image25_agent/__init__.py` uses `pkgutil.extend_path` so Python can find
the installed upstream package modules. There is intentionally **no local
`client.py` override**: payload construction, Codex Responses requests,
stream parsing, reference handling, output validation and error behavior are
provided by upstream AgentKit 0.3.1.

## Why the native Images override was removed

The previous local transport replaced the upstream Responses flow with
`/backend-api/codex/images/generations` and `/backend-api/codex/images/edits`.
That alternate path returned HTTP 403 in the application, while a text-only
Test Token did not validate image-generation access. The upstream project
documents and validates its own experimental ChatGPT/Codex Responses workflow,
including a successful Sunburst generation and Flare reference edit for the
specific configuration recorded in its release evidence.

This alignment does not guarantee that every account or optional setting is
available. The backend is experimental; if generation still fails, use the
full sanitized upstream error to diagnose the actual response instead of
switching to undocumented endpoints.

The Vercel provider is separate and is not changed by this integration.
