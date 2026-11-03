# Image Generation API

Rizky Moto AI exposes an OpenAI-compatible image generation endpoint for trusted server-side clients.

## Configure

Set these values in the server environment, then clear cached configuration:

```dotenv
RIZKY_IMAGE_API_KEY=replace-with-a-long-random-client-key
AI_GATEWAY_API_KEY=your-vercel-ai-gateway-key
AI_GATEWAY_BASE_URL=https://ai-gateway.vercel.sh/v1
AI_GATEWAY_IMAGE_MODEL=openai/gpt-image-1
```

Generate a client key with a password manager or `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Do not commit either key.

## Request

`POST /api/v1/images/generations` (JSON, Bearer authentication). The endpoint is limited to 10 requests per minute per client IP.

```bash
curl -X POST https://your-domain.example/api/v1/images/generations \
  -H "Authorization: Bearer $RIZKY_IMAGE_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"prompt":"A studio product photo of a classic motorcycle helmet","size":"1024x1024","quality":"medium"}'
```

Supported fields: `prompt` (required, up to 4000 characters), `model`, `size` (`1024x1024`, `1536x1024`, `1024x1536`, `auto`), `quality` (`low`, `medium`, `high`, `auto`), and `n` (1–4). Successful provider responses are returned unchanged, including the standard `data[].b64_json` image result.

Call this API from your own backend. Putting the bearer key in browser JavaScript makes it visible to visitors.
