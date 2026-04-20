# Tech Stack: Komunalka

## Language & Runtime
- **PHP:** 8.2+
- **TypeScript:** 5.9+
- **Node.js:** Modern version for pnpm package manager.

## Backend Framework (Laravel 13)
- **Modular Architecture:** `nwidart/laravel-modules` for domain-specific organization.
- **Actions Pattern:** `lorisleiva/laravel-actions` for business logic encapsulation.
- **WebSockets:** `laravel/reverb` for real-time events.
- **Authentication:** Session-based with OAuth integration via `laravel/socialite`.
- **Media Management:** `spatie/laravel-medialibrary`.
- **PDF Generation:** `spatie/laravel-pdf`.

## Frontend Framework
- **SPA Bridge:** `Inertia.js v2` (@inertiajs/react 3.0.0-beta.5).
- **View Library:** `React 19`.
- **Form Handling:** `react-hook-form` and `@inertiajs/react` useForm.
- **Styling:** `Tailwind CSS v4` with `@tailwindcss/vite` and `tailwind-variants`.
- **Charts:** `recharts`.
- **Icons:** `lucide-react`.

## Database & Caching
- **Main Database:** `PostgreSQL 17`.
- **Cache & Queues:** `Redis 7.2`.

## Server & Infrastructure
- **Application Server:** `FrankenPHP` (Laravel Octane).
- **Environment:** `Docker Compose` (local setup).
- **HTTPS:** `Caddy` (via FrankenPHP) with automatic local certificates.

## Development & Testing
- **Testing Framework:** `Pest 4`.
- **Static Analysis:** `PHPStan` (level 7).
- **Code Formatting:** `Laravel Pint`.
- **Task Management:** `Laravel Boost` (MCP tools).
