# 9. Usage Scenarios

Step-by-step scenarios for typical tasks — from first login to the monthly routine.

## Scenario 1: First-time setup (new user)

1. **Registration**: `/register` → fill out the form or click "Via Google" / "Via GitHub". You'll land directly on the Dashboard (it's still empty at this point).
2. **Add an address**: sidebar → "My Addresses" → "Add first address" → choose the property type → fill in the region, city, street, house number → "Save". It's a good idea to mark the first address as primary.
3. **Add providers**: sidebar → "Providers" → "Add provider". For each service (electricity, gas, water...) create a separate provider: choose the address, service type, enter a name and rate (price per unit in UAH). For electricity with a night tariff, add two rates ("Day", "Night").
4. **Add meters**: sidebar → "Meters" → create a meter for each service: address → service type → provider → serial number, name → **initial reading** (the current numbers on the meter) → optionally a photo.
5. Done — you can now start entering readings.

> Order matters: address → provider → meter. If you create a meter before a provider, you can link it to a provider later by editing the meter.

## Scenario 2: Monthly reading entry

1. Dashboard → **"Add reading"** button (or sidebar → "Enter readings").
2. Choose the address.
3. In each meter's card, enter the current reading (for multi-rate meters — per rate), adjust the date if needed, and attach a photo of the meter.
4. Check the calculated consumption and cost in the summary table.
5. Save — "Reading saved successfully!" You'll see the new entries in the history.
6. Return to the Dashboard — the consumption chart and expense breakdown will update.

## Scenario 3: Changing a provider's rate

Rates cannot be edited, but a meter can be reassigned:

1. Create a new provider with the current rates (the old calculation history is preserved).
2. Open each meter of the old provider → "Edit" → change the provider to the new one.
3. Optionally delete the old provider — the meters won't be affected, but its rates will disappear.

New readings will be calculated using the new rates; old entries will keep the calculations based on the old ones.

## Scenario 4: Replacing a meter

1. Old meter: "Meters" → "Edit" → uncheck the active flag (don't delete it, to preserve the reading history).
2. Create a new meter with the serial number of the new device and the **initial readings** it was installed with.
3. Continue entering readings against the new meter.

## Scenario 5: Correcting an erroneous reading

1. Go to the reading history (enter readings → history page, or right after saving).
2. Find the incorrect entry, click the delete button, confirm.
3. Enter the correct reading again via "Enter readings".

> If new readings have already been entered after the erroneous one, delete all entries after the mistake and re-enter them in chronological order — the "previous value" is always taken from the last entry.

## Scenario 6: Moving / new apartment

1. Add a new address ("My Addresses" → "Add address"), and make it primary if needed.
2. Create providers and meters for it.
3. You can keep the old address (for history) or delete it — in which case its providers and meters will disappear from your lists permanently.

## Scenario 7: Social login and access management

1. If you registered with an email and password, go to "Settings → Security → Connected accounts" and click "Connect" next to Google or GitHub — after confirming, you'll be able to log in with one click.
2. To disconnect a social account, click "Disconnect" and confirm with your password.
3. If you registered via a social account and want to log in with a password — you'll first need to set a password (see [Section 7.2](07-settings.md#72-security)).
