# Image Generation API

The API is an external entry point to the same generation pipeline used by the website. Requests create a normal Generation record, store the source photos, and dispatch GenerateOpenAiImageJob to the provider-specific queue. API-created generations appear in the existing Generate page and Recent History with the product thumbnail, Store, Template, progress, and final images.

## Authentication

Create a key in Settings -> API Keys. Send it as a Bearer token. Keys belong to their creating user and can optionally be restricted to one Store. The raw secret is shown once; only its SHA-256 hash is stored. Revoking a key takes effect immediately. Default rate limit is 60 requests per minute per key.

## Create a generation

POST /api/v1/images/generations (multipart/form-data)

| Field | Required | Description |
|---|---|---|
| store_id | Yes | Active Store ID |
| template_id | Yes | Active Template ID belonging to the selected Store |
| product_image | Yes | Product/package photo; JPG, PNG, or WEBP, max 10 MB |
| installed_image | No | Optional photo showing the part installed on a motorcycle |
| model | No | Model available for the active provider; defaults to the first available model |
| custom_title | No | Exact product title, max 120 characters |
| image_count | No | 1-4, defaults to 1 |

The server resolves the Store logo and Template prompt. Never upload the Store logo as a product reference. Output remains square and low quality under current generator rules.

Example:
```bash
curl -X POST "https://YOUR_DOMAIN/api/v1/images/generations" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "store_id=1" -F "template_id=2" \
  -F "product_image=@./product.jpg" \
  -F "installed_image=@./installed.jpg" \
  -F "custom_title=Kampas Rem Depan Original" -F "image_count=2"
```

Returns 202 Accepted with generation ID and status URL. The request is asynchronous.

## Status and result

- GET /api/v1/images/generations/{generation} - status, progress, Store, Template, title, and result image URLs.
- GET /api/v1/images/generations/{generation}/images/{image} - retrieve a result image using the Bearer key.
- POST /api/v1/images/generations/{generation}/cancel - cancel only while queued or pending.

Status values use the website's existing lifecycle: queued, processing, completed, failed, cancelled. New keys receive images:generate, images:read, and images:cancel scopes. Provider credentials are never exposed by the API.

## Deployment

Run php artisan migrate --force after deployment, then php artisan route:list --path=api/v1. Ensure the existing queue workers are running.
