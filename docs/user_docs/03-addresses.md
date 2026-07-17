# 3. Addresses

An address is the base entity of the system: providers and meters are attached to it. **Without at least one address, you cannot create a provider or a meter.**

## 3.1 Address list ("My Addresses")

The "My Addresses" page shows your addresses as a grid of cards. Each card includes:

- a property type badge with an icon (Apartment / Private house / Office);
- a **"Primary"** badge with a star — for the primary address (the card is highlighted with a colored border);
- the address title (street, building number), with the city, region, postal code, and apartment/office number below;
- an actions menu (three dots): **Edit**, **Delete**.

If there are no addresses yet, an empty state "You don't have any saved addresses yet" is shown with a **"Add your first address"** button.

## 3.2 Creating an address

The **"Add address"** button opens a three-step wizard: **Property type → Address → Confirmation**.

1. **Choose the property type** (Apartment, Private house, Office) — until a type is selected, the remaining fields are locked and the save button is disabled.
2. **Fill in the address:**

| Field | Required | Constraints |
|---|---|---|
| Region | yes | choice of 27 regions of Ukraine |
| City | yes | up to 255 characters |
| Street | yes | up to 255 characters |
| Building number | yes | up to 50 characters |
| Apartment/office | no | up to 50 characters |
| Postal code | no | up to 20 characters |
| Notes | no | up to 1000 characters |

3. Optionally enable **"Set as primary address"** — there can only be one primary address: the flag will automatically be removed from the previous primary address. The primary address is used as the default on the meter reading submission page and is marked "(primary)" in selection lists.
4. Click **"Save"** — you will return to the list with the message "Address created".

## 3.3 Editing an address

Clicking the card title or the "Edit" menu item opens the same form (without the stepper). You can change any fields, including the property type and the primary address flag.

## 3.4 Deleting an address

The "Delete" item opens a confirmation dialog **"Delete address?"** with the address name and the warning "This action cannot be undone".

**Consequences of deletion:**
- the address disappears from your lists (flash message "Address deleted");
- providers and meters for this address become unavailable in the interface (provider and meter lists are built from your active addresses);
- if the primary address is deleted, another address does **not** automatically become primary — assign a new primary address manually via editing;
- restoration through the interface is not possible.

Before deleting an address, make sure you no longer need the reading history for it.
