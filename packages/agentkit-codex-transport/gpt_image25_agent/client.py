from __future__ import annotations

import base64
import json
import os
import uuid
from pathlib import Path
from typing import Any

from .files import atomic_write_image, ref_to_data_url, validate_mask
from .models import DEFAULT_IMAGE_MODEL, resolve_size, validate_image_options
from .redaction import sanitize_error_text

CODEX_BASE_URL = "https://chatgpt.com/backend-api/codex"
API_IMAGE_MODEL = DEFAULT_IMAGE_MODEL
DEFAULT_HOST_MODEL = "gpt-5.5"


class ClientError(RuntimeError):
    pass


def _account_headers(token: str) -> dict[str, str]:
    headers = {
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
        "Content-Type": "application/json",
        "User-Agent": "codex_cli_rs/0.0.0",
        "originator": "codex_cli_rs",
        "x-codex-image-turn-id": str(uuid.uuid4()),
    }
    try:
        parts = token.split(".")
        if len(parts) >= 2:
            encoded = parts[1]
            encoded += "=" * (-len(encoded) % 4)
            claims = json.loads(base64.urlsafe_b64decode(encoded))
            auth = claims.get("https://api.openai.com/auth", {})
            if isinstance(auth, dict):
                account_id = auth.get("chatgpt_account_id")
                if isinstance(account_id, str) and account_id:
                    headers["ChatGPT-Account-ID"] = account_id
                residency = auth.get("chatgpt_data_residency") or auth.get("chatgpt_compute_residency")
                if isinstance(residency, str) and residency.strip():
                    headers["x-openai-internal-codex-residency"] = residency.strip()
    except Exception:
        pass
    return headers


def build_payload(
    prompt: str, *, host_model: str, quality: str, aspect: str, refs: list[Path],
    image_model: str = DEFAULT_IMAGE_MODEL, size: str | None = None,
    background: str = "opaque", action: str = "auto",
    output_format: str = "png", output_compression: int | None = None,
    mask: Path | None = None,
) -> dict[str, Any]:
    resolved_size = resolve_size(aspect, size)
    validate_image_options(image_model=image_model, quality=quality, size=resolved_size,
                           background=background, output_format=output_format,
                           output_compression=output_compression)
    if action not in {"auto", "generate", "edit"}:
        raise ValueError("Action must be auto, generate, or edit.")
    if action == "edit" and not refs:
        raise ValueError("The edit action requires at least one input image.")
    if mask is not None:
        if action != "edit" or not refs:
            raise ValueError("A mask requires action='edit' and a base image as the first input.")
        validate_mask(mask, refs[0])
        raise ValueError("The native Codex Images endpoint does not currently support AgentKit mask inputs.")
    body: dict[str, Any] = {
        "prompt": prompt,
        "model": image_model,
        "n": 1,
        "quality": quality,
        "size": resolved_size,
        "background": background,
    }
    if output_format != "png":
        body["output_format"] = output_format
    if output_compression is not None:
        body["output_compression"] = output_compression
    if refs and action != "generate":
        body["images"] = [{"image_url": ref_to_data_url(ref)} for ref in refs]
    return body


def generate_image(
    *, prompt: str, refs: list[Path], out: Path, token: str, host_model: str,
    quality: str, aspect: str, timeout: float, overwrite: bool,
    image_model: str = DEFAULT_IMAGE_MODEL, size: str | None = None,
    background: str = "opaque", action: str = "auto",
    output_format: str = "png", output_compression: int | None = None,
    mask: Path | None = None,
) -> Path:
    import httpx

    payload = build_payload(
        prompt, host_model=host_model, quality=quality, aspect=aspect, refs=refs,
        image_model=image_model, size=size, background=background, action=action,
        output_format=output_format, output_compression=output_compression, mask=mask,
    )
    has_refs = bool(payload.get("images"))
    endpoint = "images/edits" if has_refs else "images/generations"
    headers = _account_headers(token)
    timeout_cfg = httpx.Timeout(timeout, connect=30.0, read=timeout, write=60.0, pool=30.0)
    try:
        with httpx.Client(timeout=timeout_cfg, headers=headers) as client:
            response = client.post(f"{CODEX_BASE_URL}/{endpoint}", json=payload)
        try:
            response.raise_for_status()
        except httpx.HTTPStatusError as exc:
            body = sanitize_error_text(response.text)
            try:
                parsed = response.json()
                error = parsed.get("error", {}) if isinstance(parsed, dict) else {}
                message = error.get("message") if isinstance(error, dict) else None
                if isinstance(message, str) and message.strip():
                    body = message.strip()
            except Exception:
                pass
            raise ClientError(f"Codex Images API HTTP {response.status_code}: {body[:1200]}") from exc
        result = response.json()
    except ClientError:
        raise
    except Exception as exc:
        raise ClientError(sanitize_error_text(f"Codex Images request failed: {type(exc).__name__}: {exc}")) from exc

    data = result.get("data") if isinstance(result, dict) else None
    image_b64 = data[0].get("b64_json") if isinstance(data, list) and data and isinstance(data[0], dict) else None
    if not isinstance(image_b64, str) or not image_b64:
        raise ClientError("Codex Images API returned HTTP success without data[0].b64_json.")
    try:
        image_bytes = base64.b64decode(image_b64, validate=True)
    except Exception as exc:
        raise ClientError("Codex Images API returned invalid base64 image data.") from exc
    atomic_write_image(out, image_bytes, output_format=output_format, overwrite=overwrite)
    return out
