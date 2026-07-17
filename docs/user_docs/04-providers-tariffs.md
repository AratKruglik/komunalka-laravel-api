# 4. Providers and Tariffs

A provider is a utility service supplier for a specific address (for example, "Kyivvodokanal" for cold water). Provider tariffs are used to automatically calculate the cost of consumption when readings are submitted.

**Prerequisite:** to add a provider, you must have at least one address.

## 4.1 Provider List (`Providers`)

The "My Providers" page shows provider cards. Each card includes:

- name and service type badge;
- description, website link, phone, email (if filled in);
- a **"TARIFFS"** block — chips with the tariff name, price per unit (e.g. `4.32 UAH/kWh`, units in international format), and standing charge (if greater than zero);
- buttons: **Edit** (pencil), **Delete** (trash can).

Empty state: "No providers yet" + **"Add Provider"** button.

## 4.2 Creating a Provider

The **"Add Provider"** button opens a form:

| Field | Required | Notes |
|---|---|---|
| Address | yes | selected from your addresses; the primary one is marked "(primary)". **The address cannot be changed after creation** |
| Service type | yes | Electricity, Gas, Cold water, Hot water, Heating, Sewerage |
| Provider name | yes | e.g. "Kyivvodokanal" |
| Notes | no | up to 1000 characters |
| Support phone | no | up to 50 characters |
| Contact e-mail | no | valid address |
| Official website | no | valid URL |

### Provider Tariffs

The "Provider Tariffs" block (hint: "Add daytime, nighttime, or other plans") is available **only during creation**. At least one tariff is required; the **"Add Tariff"** button adds subsequent ones — for example, daytime and nighttime tariffs for electricity. After selecting the service type, a unit of measure appears next to the base rate (e.g. `UAH/kWh`).

Tariff fields:

| Field | Required | Notes |
|---|---|---|
| Tariff name | yes | defaults to "Base tariff" |
| Base rate | yes | UAH per unit of service measurement, number ≥ 0 |
| Standing charge | no | UAH, fixed component |

The tariff start date is automatically set to today, and the currency is hryvnia.

After saving, you return to the list with the message "Provider created."

> **Multi-tariff accounting:** if a provider has multiple tariffs, when entering readings for a meter belonging to this provider, the system will offer a separate reading field **for each tariff** (for more details, see [Section 6](06-readings.md)).

## 4.3 Editing a Provider

The edit form allows changing the service type, name, and contact details. Restrictions:

- **Address is locked** — a provider cannot be moved to a different address; create a new provider if needed.
- **Tariffs cannot be changed via the edit form** — the tariffs block is hidden. Existing tariffs remain in effect.
- There is no provider activity toggle in the form.

## 4.4 Deleting a Provider

The trash can button opens a **"Delete provider?"** dialog. Consequences:

- all of the provider's tariffs are deleted;
- meters linked to the provider **remain**, but lose their link — new readings for them will no longer have automatic cost calculation;
- previously submitted readings and their calculations are preserved in the history.
