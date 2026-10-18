# NVIDIA Qwen-Image-Edit-2511 setup

The NVIDIA provider supports `qwen/qwen-image-edit-2511` through a deployed NVIDIA Visual GenAI NIM endpoint. The public NVIDIA Build Trial endpoint is not configured as the image-edit endpoint for this model.

## 1. Deploy the NIM

Use NVIDIA's official deployment guide:
https://build.nvidia.com/qwen/qwen-image-edit/deploy?nim=self-hosted

Select model version `qwen-image-edit-2511`. The NIM exposes the OpenAI-compatible image editing route at `/v1/images/edits`.

## 2. Configure the application

Set `NVIDIA_QWEN_ENDPOINT` to the full image-edit URL reachable from the Laravel app/queue workers. Example only:

```dotenv
NVIDIA_QWEN_ENDPOINT=http://127.0.0.1:8000/v1/images/edits
NVIDIA_TIMEOUT=600
```

If NIM is on another host, use that host's private/reachable URL instead. Do not expose an unauthenticated NIM endpoint publicly. The current provider sends the configured NVIDIA API key as a Bearer token; configure the endpoint/gateway to accept it, or adjust authentication in the provider to match your secured deployment.

The NVIDIA credential pool still needs an active NVIDIA key saved in AI Provider Configuration.

## 3. Verify readiness

From the Laravel project directory:

```bash
php artisan optimize:clear
php artisan test --filter=NvidiaProviderTest
```

Then select **NVIDIA NIM** as the active provider and **Qwen Image Edit 2511** as the model. Keep provider failover disabled while testing NVIDIA by itself.

## Reference behavior

- Product packaging/photo is mandatory.
- Installed-on-motorcycle photo is optional.
- The selected Store's official logo is mandatory and is included in the reference board.
- Template preview imagery is not attached; Template instructions are passed in the prompt.
- Qwen is sent the reference board as a data URL in the JSON request body to `/v1/images/edits`.

A successful PHP test only verifies application-level behavior; a real image generation test requires a running, reachable NIM deployment and a valid credential.
