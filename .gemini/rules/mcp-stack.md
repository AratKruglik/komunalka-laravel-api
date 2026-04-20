# MCP Stack — Tool Usage Guide

## Laravel Boost (Primary — Laravel Ecosystem)

| Tool | When to Use |
|------|-------------|
| `search-docs` | First choice for Laravel, Inertia, Ziggy, Spatie, Pest, Socialite, Reverb docs |
| `application-info` | Installed models, packages, versions |
| `database-schema` | View table structure before writing queries or migrations |
| `database-query` | Read-only DB queries |
| `list-routes` | Verify routes before creating links |
| `tinker` | Debug PHP code, test Eloquent queries |
| `last-error` | Get last exception for debugging |
| `browser-logs` | Read recent browser errors/exceptions |
| `get-absolute-url` | Generate correct project URLs |

Search syntax: simple words use auto-stemming; use `"quoted phrase"` for exact-order; combine words for AND logic; pass multiple queries for OR.

## Herd (Local PHP + Sites)

Only available in local (non-sandbox) environments.

| Tool | When to Use |
|------|-------------|
| `get_all_php_versions` | Inspect installed PHP versions via Herd |
| `get_all_sites` | List sites served by Herd |
| `get_site_information` | Inspect the current site (`.test` URL, isolation, PHP version) |
| `isolate_or_unisolate_site` | Switch PHP version for the site |
| `secure_or_unsecure_site` | Toggle HTTPS |

## Context7 (Frontend / Non-Laravel Libraries)

| Tool | When to Use |
|------|-------------|
| `resolve-library-id` | Find library ID before querying |
| `query-docs` | React 19, Tailwind v4, Tailwind Variants, Vite, TypeScript, pnpm — libs not covered by Laravel Boost |

## GitHub MCP

| Tool | When to Use |
|------|-------------|
| `pull_request_read` | Read PR details for review |
| `create_pull_request` | Create PR (`docs-writer` agent only) |
| `pull_request_review_write` | Post inline review comments (`reviewer` agent) |
| `list_pull_requests` | List open PRs |

## Figma MCP

| Tool | When to Use |
|------|-------------|
| `get_figma_data` | Inspect designs before implementing |
| `download_figma_images` | Download design assets |

## Browser Agent (Experimental, built-in Gemini CLI)

Use for quick orchestrator-level smoke checks. For full E2E suites, prefer the `qa` agent with Pest 4 Browser tests.

## Playwright MCP (optional, via extension)

If installed, use through the `qa` agent for browser automation beyond the built-in `browser_agent`.
