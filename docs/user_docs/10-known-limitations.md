# 10. Known Limitations of the Current Version

A list of limitations and defects confirmed through end-to-end browser testing (registration → addresses → providers → meters → readings → settings → account deletion).

## Fixed Defects

Three account-deletion defects found during browser testing have been **fixed and covered by regression tests** (16.07.2026):

- ~~An account with "Remember me" enabled could not be deleted~~ — logout is now performed **before** the user is deleted, so the remember-token cycling no longer resurrects the record. Covered by feature tests in `tests/Feature/Web/Auth/SettingsControllerTest.php`.
- ~~The first submission of the account deletion dialog always failed~~ — the password field is now bound to the form state, so the first click submits the entered password. Covered by Pest 4 browser tests in `tests/Browser/Settings/SettingsPageTest.php`.
- ~~OAuth users could not delete their account~~ — if a password was never set, confirmation is now done by entering the user's own email or username (see [section 7.4](07-settings.md)). The right to be forgotten (GDPR, Art. 17) has been restored.

## Functional Limitations

| Feature | Details | Workaround |
|---|---|---|
| Email cannot be changed | The field is locked in profile settings | None; create a new account if needed |
| Tariffs cannot be edited | The tariff block is only available when creating a provider | Create a new provider with new tariffs and reassign meters ([scenario 3](09-user-scenarios.md#scenario-3-changing-a-providers-rate)) |
| Provider/meter address cannot be changed | Fields are locked when editing; the meter's service type is locked too | Create a new entity at the desired address |
| Meter provider is not filtered by service type | The select offers all providers for the address (e.g., for an electricity meter, the water utility is also shown) | Manually choose the provider for the correct service |
| Readings cannot be edited | Only creation and deletion are available | Delete the incorrect entry and add the correct one |
| Duplicate readings are not blocked | Multiple readings can be entered for the same date | Check the history before entering a new reading |
| History does not show the tariff name | Day/Night entries for a multi-tariff meter look identical | Distinguish them by the reading values |
| The "Billing address" selector on the Dashboard does not filter | Statistics are always shown across all addresses | — |
| No new primary address is set after deleting the primary one | The primary flag is not transferred to another address | Assign a new primary address manually via editing |
| No reading recognition from photos | Photos serve only as reference confirmation | Values must be entered manually |
| Chat history is not saved | The conversation with the assistant disappears after a page reload | — |

## UI Elements That Don't Work Yet

- **"Export data (CSV)"** in "Settings → Account" — the button has no handler (verified: clicking does nothing); reading export (CSV/PDF) is prepared in the code, but the routes are not registered.
- **"View tariffs"** in the Dashboard's "Quick Actions" — leads to `/tariffs`, which returns a plain "404 Not Found" page without the application layout (tariffs can be viewed on the cards in the "Providers" section).
- **Notification bell** in the top bar — decorative (verified: clicking opens nothing).
- **Paperclip button** in the assistant chat — attachments are not supported.
- Footer links ("Terms of Use", "Privacy Policy", "Contacts") — placeholder `#` links.
- **"Forgot password?"** — the link is missing on the login page; the recovery page is only accessible via the direct URL `/forgot-password`.

## Minor UI Details

- **The login page does not show flash messages**: after a password reset ("Password successfully reset…") and after account deletion ("Your account has been deleted."), the backend sends a message, but the Login page does not render it — the user just sees the login form.
- **Mixed message languages**: registration/login validation is in English (Laravel defaults: "The first name field is required.", "Invalid credentials."), while the rest of the interface is in Ukrainian.
- **Inconsistent units of measurement**: provider cards and the Dashboard use international units (kWh, m³), while the reading-entry page uses localized units (кВт·год, м³).
- **Dual assistant name**: the help header/widget says "КомуШІшка", while the greeting says "KhatkoBot" (an incomplete renaming).
- **Guest page footer** shows a hardcoded "© 2023", while authenticated pages show the current year.

## Infrastructure Dependencies (for administrators)

- Photo processing (thumbnails) is queued on **redis** (`MEDIA_QUEUE_CONNECTION`, default `redis`), while the standard worker listens on the default queue (`database`) — without a separate worker for the redis photo queue, photos remain stuck in the "Processing photo…" state forever.
- The application container must be connected to `ka-redis-network` (declared in compose.yml) — otherwise photo uploads fail with a 500 error (`getaddrinfo for redis failed`).
