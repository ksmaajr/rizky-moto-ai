from __future__ import annotations

import base64
import json
import uuid
from collections.abc import Iterable
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


def build_payload(
    prompt: str, *, host_model: str, quality: str, aspect: str, refs: list[Path],
    image_model: str = DEFAULT_IMAGE_MODEL, size: str | None = None,
    background: str = "opaque", action: str = "auto",
    output_format: str = "png", output_compression: int | None = None,
    mask: Path | None = None,
) -> dict[str, Any]:
    """Build the native Codex Images JSON body (not a Responses hosted-tool payload)."""
    resolved_size = resolve_size(aspect, size)
    validate_image_options(
        image_model=image_model, quality=quality, size=resolved_size,
        background=background, output_format=output_format,
        output_compression=output_compression,
    )
    if action not in {"auto", "generate", "edit"}:
        raise ValueError("Action must be auto, generate, or edit.")
    if action == "edit" and not refs:
        raise ValueError("The edit action requires at least one input image.")
    if mask is not None and action != "edit":
        raise ValueError("A mask requires action='edit' and a base image as the first input.")
    if mask is not None:
        if not refs:
            raise ValueError("A mask requires an input image.")
        mask = validate_mask(mask, refs[0])

    payload: dict[str, Any] = {
        "model": image_model,
        "prompt": prompt,
        "n": 1,
        "size": resolved_size,
        "quality": quality,
        "background": background,
    }
    if refs:
        payload["images"] = [{"image_url": ref_to_data_url(ref)} for ref in refs]
    if mask is not None:
        payload["mask"] = {"image_url": ref_to_data_url(mask)}
    return payload


def iter_sse_json(response: Any) -> Iterable[dict[str, Any]]:
    event_name = None
    data_lines: list[str] = []
    def flush() -> dict[str, Any] | None:
        nonlocal event_name, data_lines
        if not data_lines:
            event_name = None
            return None
        raw = "\n".join(data_lines).strip()
        event = event_name
        event_name = None
        data_lines = []
        if not raw or raw == "[DONE]":
            return None
        payload = json.loads(raw)
        if isinstance(payload, dict) and event and "type" not in payload:
            payload["type"] = event
        return payload if isinstance(payload, dict) else {"value": payload}
    for line in response.iter_lines():
        if isinstance(line, bytes):
            line = line.decode("utf-8", errors="replace")
        line = str(line)
        if line == "":
            payload = flush()
            if payload is not None:
                yield payload
            continue
        if line.startswith(":"):
            continue
        if line.startswith("event:"):
            event_name = line[len("event:"):].strip()
        elif line.startswith("data:"):
            data_lines.append(line[len("data:"):].lstrip())
    payload = flush()
    if payload is not None:
        yield payload


def extract_image_b64(value: Any) -> str | None:
    """Find a final image result; streamed preview images are never final output."""
    found = None
    if isinstance(value, dict):
        if (value.get("type") == "image_generation_call"
                and value.get("status") == "completed"
                and isinstance(value.get("result"), str)):
            found = value["result"]
        for child in value.values():
            nested = extract_image_b64(child)
            if nested:
                found = nested
    elif isinstance(value, list):
        for child in value:
            nested = extract_image_b64(child)
            if nested:
                found = nested
    return found


def _raise_stream_error(event: dict[str, Any]) -> None:
    failed_statuses = {"failed", "incomplete", "cancelled", "canceled", "error"}
    for node in _objects(event):
        event_type = str(node.get("type", ""))
        status = node.get("status")
        if event_type.rsplit(".", 1)[-1] not in failed_statuses and status not in failed_statuses:
            continue
        response = node.get("response")
        response = response if isinstance(response, dict) else {}
        detail = (response.get("error") or response.get("incomplete_details")
                  or node.get("error") or node.get("incomplete_details")
                  or node.get("message") or node.get("code"))
        description = str(event_type or status)
        if detail:
            description += f": {json.dumps(detail, ensure_ascii=False)}"
        raise ClientError(sanitize_error_text(f"backend stream failed: {description}"))


def _objects(value: Any) -> Iterable[dict[str, Any]]:
    if isinstance(value, dict):
        yield value
        for child in value.values():
            yield from _objects(child)
    elif isinstance(value, list):
        for child in value:
            yield from _objects(child)


class _ImageStreamState:
    """Reconcile final call events without promoting previews or truncated streams."""

    def __init__(self) -> None:
        self.completed = False
        self.call_ids: set[str] = set()
        self.statuses: dict[str | None, str | None] = {}
        self.results: dict[str | None, str] = {}

    def consume(self, event: dict[str, Any]) -> None:
        _raise_stream_error(event)
        event_type = event.get("type")
        terminal = event_type == "response.completed"
        if terminal:
            response = event.get("response")
            if not isinstance(response, dict) or response.get("status") != "completed":
                raise ClientError("backend response.completed lacks a completed response status")
            self.completed = True
            calls = _objects(response.get("output", []))
        elif event_type in {"response.output_item.added", "response.output_item.done"}:
            calls = _objects(event.get("item", {}))
        else:
            return
        for call in calls:
            if call.get("type") != "image_generation_call":
                continue
            call_id = call.get("id")
            call_id = call_id if isinstance(call_id, str) and call_id else None
            if call_id is not None:
                self.call_ids.add(call_id)
                if len(self.call_ids) > 1:
                    raise ClientError("backend returned multiple image_generation calls; a single output is required")
            status = call.get("status")
            self.statuses[call_id] = status
            if terminal and status != "completed":
                raise ClientError("backend completed with a non-completed image_generation call")
            if (terminal or event_type == "response.output_item.done") and status == "completed":
                result = call.get("result")
                if isinstance(result, str) and result:
                    previous = self.results.get(call_id)
                    if previous is not None and previous != result:
                        raise ClientError("backend returned conflicting image_generation results; a single output is required")
                    self.results[call_id] = result

    def final_result(self) -> str:
        if not self.completed:
            raise ClientError("backend stream ended without response.completed")
        if any(status != "completed" for status in self.statuses.values()):
            raise ClientError("backend returned a non-completed image_generation call")
        results = set(self.results.values())
        if len(results) > 1:
            raise ClientError("backend returned multiple image_generation results; a single output is required")
        if not results:
            raise ClientError("backend returned no image_generation result")
        return results.pop()


def _codex_native_headers(token: str) -> dict[str, str]:
    """Build the identity/account headers used by the native Codex Images endpoints."""
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json",
        "Accept": "application/json",
        "User-Agent": "codex_cli_rs/0.0.0",
        "originator": "codex_cli_rs",
        "x-codex-image-turn-id": str(uuid.uuid4()),
    }
    try:
        parts = token.split(".")
        if len(parts) >= 2:
            payload = parts[1] + "=" * (-len(parts[1]) % 4)
            claims = json.loads(base64.urlsafe_b64decode(payload))
            auth = claims.get("https://api.openai.com/auth", {})
            account_id = auth.get("chatgpt_account_id")
            if isinstance(account_id, str) and account_id:
                headers["ChatGPT-Account-ID"] = account_id
            residency = auth.get("chatgpt_data_residency") or auth.get("chatgpt_compute_residency")
            if isinstance(residency, str) and residency.strip():
                headers["x-openai-internal-codex-residency"] = residency.strip()
    except Exception:
        # Keep malformed tokens on the normal authenticated request path so the
        # backend returns the real auth error instead of crashing locally.
        pass
    return headers


def _post_native_image_request(
    *, token: str, prompt: str, image_model: str, size: str, quality: str,
    refs: list[Path], background: str, output_format: str, timeout: float,
    mask: Path | None = None,
) -> dict[str, Any]:
    """Call the dedicated Codex Images API instead of the stale Responses hosted tool."""
    import httpx

    base_url = CODEX_BASE_URL.rstrip("/")
    path = "/images/edits" if refs else "/images/generations"
    payload: dict[str, Any] = {
        "model": image_model,
        "prompt": prompt,
        "n": 1,
        "size": size,
        "quality": quality,
        "background": background,
    }
    if refs:
        payload["images"] = [{"image_url": ref_to_data_url(ref)} for ref in refs]
    if mask is not None:
        # Keep mask behavior explicit; native endpoint implementations vary in
        # their mask schema, so never silently drop a caller-supplied mask.
        payload["mask"] = {"image_url": ref_to_data_url(mask)}

    timeout_cfg = httpx.Timeout(timeout, connect=30.0, read=timeout, write=60.0, pool=30.0)
    try:
        with httpx.Client(timeout=timeout_cfg, headers=_codex_native_headers(token)) as client_http:
            response = client_http.post(f"{base_url}{path}", json=payload)
        if response.status_code >= 400:
            try:
                body = response.json()
                error = body.get("error", {}) if isinstance(body, dict) else {}
                detail = error.get("message") if isinstance(error, dict) else None
            except Exception:
                detail = None
            detail = str(detail or response.text or "no error detail")
            raise ClientError(
                f"Codex Images API HTTP {response.status_code}: "
                f"{sanitize_error_text(detail[:1200])}"
            )
        try:
            result = response.json()
        except Exception as exc:
            raise ClientError("Codex Images API returned invalid JSON") from exc
        if not isinstance(result, dict):
            raise ClientError("Codex Images API returned a non-object response")
        return result
    except ClientError:
        raise
    except Exception as exc:
        raise ClientError(
            sanitize_error_text(f"Codex Images API request failed: {type(exc).__name__}: {exc}")
        ) from exc


def generate_image(
    *,
    prompt: str, refs: list[Path], out: Path, token: str, host_model: str,
    quality: str, aspect: str, timeout: float, overwrite: bool,
    image_model: str = DEFAULT_IMAGE_MODEL, size: str | None = None,
    background: str = "opaque", action: str = "auto",
    output_format: str = "png", output_compression: int | None = None,
    mask: Path | None = None,
) -> Path:
    """Generate or edit through Codex's native Images endpoints.

    Codex's Responses endpoint rejects the hosted image_generation tool_choice.
    Use /images/generations for text-only requests and /images/edits whenever
    references are attached, preserving the caller's selected image model.
    """
    resolved_size = resolve_size(aspect, size)
    validate_image_options(
        image_model=image_model, quality=quality, size=resolved_size,
        background=background, output_format=output_format,
        output_compression=output_compression,
    )
    if action not in {"auto", "generate", "edit"}:
        raise ClientError("Action must be auto, generate, or edit.")
    if action == "edit" and not refs:
        raise ClientError("The edit action requires at least one input image.")
    if mask is not None and not refs:
        raise ClientError("A mask requires at least one input image.")

    response = _post_native_image_request(
        token=token,
        prompt=prompt,
        image_model=image_model,
        size=resolved_size,
        quality=quality,
        refs=refs,
        background=background,
        output_format=output_format,
        timeout=timeout,
        mask=mask,
    )
    data = response.get("data")
    image_b64 = (
        data[0].get("b64_json")
        if isinstance(data, list) and data and isinstance(data[0], dict)
        else None
    )
    if not isinstance(image_b64, str) or not image_b64:
        raise ClientError("Codex Images API response contained no image data.")
    try:
        decoded = base64.b64decode(image_b64, validate=True)
    except Exception as exc:
        raise ClientError("Codex Images API returned invalid base64 image data.") from exc

    # The CLI contract promises a validated image file. If the server returns
    # a different container than requested, convert only supported raster data.
    if output_format in {"jpeg", "webp"}:
        try:
            from PIL import Image
            import io
            with Image.open(io.BytesIO(decoded)) as source:
                source.load()
                converted = source.convert("RGB") if output_format == "jpeg" else source.convert("RGBA")
                buffer = io.BytesIO()
                converted.save(
                    buffer,
                    format="JPEG" if output_format == "jpeg" else "WEBP",
                    **({"quality": output_compression} if output_compression is not None else {}),
                )
                decoded = buffer.getvalue()
        except Exception as exc:
            raise ClientError(f"Could not convert returned image to {output_format}: {exc}") from exc
    atomic_write_image(out, decoded, output_format=output_format, overwrite=overwrite)
    return out
