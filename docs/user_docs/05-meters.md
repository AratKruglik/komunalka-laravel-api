# 5. Meters

A meter is linked to an address and a service type, and accumulates a history of readings.

**Prerequisite:** at least one address is required. A provider is optional, but without one, readings will not have a cost calculation.

## 5.1 Meter list (`Meters`)

The page shows meter cards with a filter by address. Each card displays:

- name and status (**Active / Inactive**);
- serial number, location, service type, provider;
- meter photo thumbnail (if uploaded);
- actions menu: **Edit**, **Delete** (with a confirmation dialog).

## 5.2 Adding a meter

The create button opens a form:

| Field | Required | Notes |
|---|---|---|
| Address | yes | **cannot be changed** after creation |
| Service type | yes | determines the unit of measurement (kWh, m³, Gcal); locked during editing |
| Provider | no | the field is disabled with a hint "Select an address first"; after selecting an address, the list shows **all** of its providers (regardless of service type — choose the provider for the corresponding service yourself) |
| Serial number | yes | up to 255 characters |
| Name | yes | up to 255 characters, e.g. "Bathroom water meter" |
| Initial reading | yes | number ≥ 0; the first consumption will be calculated from it |
| Model, location, description, notes | no | free-form reference information |
| Installation date | no | date |
| Photo | no | JPEG, PNG, GIF, or HEIC/HEIF up to 10 MB |

Fill-in order: first select the **address** — the list of available providers depends on it; then the **service type** — the corresponding unit of measurement (kWh, m³, Gcal) will appear next to the initial reading field. After saving — return to the list with the message "Meter created."

## 5.3 Meter photo

- A meter can have **one** photo (a new one replaces the previous one).
- iPhone formats (HEIC/HEIF) are supported — the server automatically converts them to JPEG.
- After uploading, the photo is processed for a while (optimized versions are created) — the card shows a "Processing photo…" indicator, and the page refreshes itself once the photo is ready (checked every 4 seconds).

> The photo is for reference only: the system **does not automatically recognize reading values from the photo**.

## 5.4 Editing a meter

You can change the name, serial number, provider, location, description, initial reading, active status, and photo. **The address and service type cannot be changed after creation** — create a new meter if needed.

The "Inactive" status should be set for meters that have been removed or replaced — they will no longer be counted as active.

## 5.5 Deleting a meter

The "Delete" option → confirmation dialog. **Deleting a meter also deletes its entire reading history.** If you need to keep the history, mark the meter as inactive instead of deleting it.
