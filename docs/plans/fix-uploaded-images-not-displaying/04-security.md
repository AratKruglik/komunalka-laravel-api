# Security Review: Виправлення відображення завантажених зображень

## Summary
- Critical: 0
- High: 0
- Medium: 1 (documented as recommendation, not fixed — out of diff scope)
- Out of scope (Low/Info): 2

No Critical or High findings. No code was changed by this review — the diff is
sound. One Medium finding is documented because it is *activated* by this fix
(the storage symlink makes media bytes publicly reachable) and was an explicit
review ask (task point 5). Its real remediation is architectural and out of
scope for this PR.

## Files reviewed (re-read from disk, not from report)
- `docker/php/entrypoint.sh` (new)
- `docker/php/Dockerfile` (prod, runtime stage)
- `docker/php/Dockerfile-dev`
- `resources/js/Components/ui/MediaThumbnail.tsx` (new)
- `resources/js/lib/media.ts` (modified)
- `resources/js/Pages/Readings/Index.tsx` (modified)
- Supporting backend authorization path: `Modules/Meter/Actions/Pages/ReadingIndexPage.php`,
  `Modules/Meter/Actions/GetReadingsByAddress.php`, `Modules/Meter/Models/MeterReading.php`,
  `Modules/Shared/Concerns/ResolvesMediaConversionUrls.php`
- `config/media-library.php`, `.dockerignore`

## Critical findings (FIXED)
None.

## High findings (FIXED)
None.

## Medium recommendations (NOT FIXED)

### 1. Publicly served media has a predictable directory path — enumeration risk — config/media-library.php:105-110
**Issue:** Media is stored on the `public` disk (`MEDIA_DISK` default `public`)
with `path_generator => DefaultPathGenerator` and `file_namer => DefaultFileNamer`.
The URL layout is therefore `/{sequential-media-id}/{filename}`
(e.g. `/storage/123/photo.jpg`, conversions under `/storage/123/conversions/photo-optimized.jpg`).
The **directory component is the auto-increment media id** — fully predictable and
enumerable. The bytes are served **directly by the web server (FrankenPHP) with no
authorization check**. The `userOwnsAddress` guard in `ReadingIndexPage` /
`GetReadingsByAddress` gates only the **Inertia props** (which URLs get rendered),
not the file endpoint itself.

**Why this is in scope for this diff:** Before this fix the `public/storage` symlink
was missing, so `/storage/...` returned 404. The new `entrypoint.sh` creates that
symlink at container start, which is exactly what makes these files reachable. The
exposure is *activated* by this PR, so it belongs in this report rather than being
waved off as a pre-existing pattern.

**Severity rationale (Medium, not High):** The uploads keep the user's **original
filename** (`addMedia($photo)` with no `usingFileName()`), so an attacker who
enumerates media-id directories still has to guess the filename to fetch a file.
That guess requirement plus the low sensitivity of the data (meter / meter-reading
photos, not credentials or top-tier PII) keeps this at Medium. If filenames were
also derived from a predictable value (reading id, timestamp), both path components
would be guessable and this would be High.

**Recommended fix:** Serve private media through an authenticated route instead of
the public disk:
- Move the media collections to a non-public disk (`->useDisk('local')` / a `private`
  disk in `config/filesystems.php`).
- Add an authenticated controller/route that resolves the media, runs the existing
  `MeterReadingPolicy` / owner check, and streams the file (`Storage::download` /
  `->response()`), returning the signed/authorized URL from the resources instead of
  `getUrl()`.
- Alternatively, if public serving must stay, switch `file_namer` /
  `path_generator` to a UUID-based generator so neither the directory nor the
  filename is guessable.

**Why deferred:** This is an architectural change to the media storage/serving model
across three models (User avatar, Meter photo, MeterReading photos) and their
resources — well beyond an image-display bugfix. It also needs a stakeholder decision
on the private-disk vs. UUID-path tradeoff. Fixing it inside this PR would violate the
minimal-impact principle and touch far more than the display path this task covers.

## Out of scope (Low / Info)

### Info 1. `entrypoint.sh` swallows `storage:link` errors with `|| true`
`php artisan storage:link || true` intentionally masks failures for idempotency /
restart-race safety. This is a deliberate availability tradeoff, not a security
weakness — the `[ ! -e ... ]` pre-check already makes it a no-op when the link
exists. No action.

### Info 2. `<a href>` on `MediaThumbnail` is not sanitized against `javascript:` schemes
React does not strip `javascript:` URLs from `href`. Here it is not reachable: the
href is `resolveMediaSrc(photo, ...) ?? photo.original_url`, and those values are
origin-absolute URLs generated server-side by Spatie `getUrl()` (`/storage/...`).
A user-controlled original filename would still resolve to an absolute app-origin
URL, never a `javascript:` scheme. `rel="noopener noreferrer"` is correctly present
on the `target="_blank"` link, so reverse-tabnabbing is also closed. No action.

## Verified clean (per OWASP Top 10 + Laravel-specific)

- **A01 Broken Access Control:** Reading photos are scoped to the authenticated user.
  `ReadingIndexPage` checks `userAddressRepository->userOwnsAddress($userId, $addressId)`
  before building resources, and `GetReadingsByAddress` re-asserts the same with
  `abort_if(...HTTP_NOT_FOUND)`. No IDOR at the props layer. (File-endpoint gap is the
  Medium above.)
- **A03 Injection (command):** `entrypoint.sh` has no `eval`, no unquoted expansion of
  untrusted data. `$PROCESS` is a compose-defined variable; `exec "$@"` runs the fixed
  `CMD` (supervisord). No injection surface.
- **A02/A05 Secrets & misconfiguration:** `.dockerignore` excludes `.env` and `.env.*`
  (allowlisting only `.env.example` / `.env.production.example`), so no secrets enter
  the build context via `COPY . .`. No hardcoded keys, no debug/APP_DEBUG changes.
  Dockerfile `COPY`/`chmod` of the entrypoint are correct; entrypoint runs as root only
  to create the symlink, then `exec`s supervisord which drops app processes to
  `www-data`.
- **A07 XSS:** `MediaThumbnail` passes `src`/`alt` as React attribute values (auto-escaped);
  no `dangerouslySetInnerHTML`. `media.ts` HEIC heuristic only chooses/suppresses a URL,
  no injection surface — worst case is a broken/omitted `<img>`, not code execution.
- **A08 CSRF / integrity:** No new state-changing routes; delete/poll flows use Inertia's
  CSRF-protected router. No untrusted deserialization.
- **A09 Logging:** No sensitive data logged by any changed file.
- **A10 SSRF:** No user-controlled outbound fetch introduced.
- **Mass assignment / raw queries:** No model `$fillable` or query changes in this diff;
  all data access goes through existing Eloquent repositories/resources.
