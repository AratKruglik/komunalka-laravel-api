# 6. Readings

Readings are entered **in batch**: all at once, for all meters at a single address, with automatic calculation of consumption and cost.

## 6.1 Entering Readings (`Enter Readings`)

1. **Select an address** — your primary address is pre-filled by default; the page will show a card for each meter at the selected address. If there are no meters at the address, a hint will appear, "No meters for the selected address," advising you to add them in the "Meters" section, and the "Save" button will be disabled.
2. **Fill in the meter cards.** Each card contains:
   - **Previous reading** (read-only) — the last entered reading or the meter's initial value;
   - **Current reading** — a field for the new value;
   - **Reading date** — defaults to today, can be changed;
   - **Photo** — you can attach a photo of the meter as proof (JPEG, PNG, WebP, HEIC/HEIF).
3. As you type, the system **calculates live**:
   - consumption = current reading − previous reading;
   - cost = consumption × tariff rate (if the meter has a provider with a tariff);
   - the summary table at the bottom shows the totals.
4. Click the save button — on success you will be taken to the reading history with the message **"Reading saved successfully!"**

### Multi-Tariff Meters

If a meter's provider has multiple tariffs (e.g., "Day" and "Night" for electricity), the meter card will show a **separate reading field for each tariff**. The previous value is tracked separately for each tariff.

### Validation Rules

- A reading **cannot be less than the previous one** — the server will reject the save with the message "The reading value cannot be less than the previous one."
- A reading cannot be negative.
- The reading date is required.
- The tariff is applied automatically: the provider's tariff in effect on the reading date is used.

## 6.2 Reading History

The "Meter Readings" page (opened after saving) requires you to **select an address** from the list — even right after saving readings, you must select the address again. Readings are displayed as cards:

- meter name and serial number;
- reading date, current and previous values, consumption;
- an **"Estimated"** badge for estimated (not actual) readings;
- notes and photo thumbnails — clicking a photo opens the full-size image in a new tab;
- a delete reading button (with confirmation).

> For a multi-tariff meter, the history will show a separate entry for each tariff, but **the tariff name (Day/Night) is not shown on the card** — entries are distinguished only by their values.

While attached photos are being processed, a processing indicator is shown; the page refreshes automatically every 4 seconds until processing completes.

## 6.3 Deleting a Reading

A reading can be deleted from the history: the "Delete reading?" dialog → "Delete" → flash message "Reading deleted successfully." Editing readings is not supported — if you made a mistake, delete the incorrect reading and enter the correct one.

> **Note:** the system does not prevent entering multiple readings for the same date. The "previous" reading is always considered to be the most recent by date for that meter (and tariff).
