# 2. Navigation and Dashboard

## 2.1 Interface Structure

After logging in, all pages share a common structure:

- **Sidebar menu** on the left:
  - *Main menu*: **Dashboard** (`/`), **My Addresses**, **Meters**, **Submit Reading**, **Providers**.
  - *Settings*: **Settings**, **Help**.
  - The active section is highlighted.
- **Top bar**: page title, theme switcher, notification bell, user menu (avatar, name, email → "My Profile", "Settings", "Log Out").
- **Floating chat button** in the bottom right — quick access to the AI assistant KhatkoBot from any page (see [section 8](08-help-assistant.md)).
- **Flash messages**: after actions (create, update, delete), a green (success) or red (error) banner appears above the content.
- **Breadcrumbs**: on address and provider create/edit pages, a navigation trail is shown above the heading (e.g. "My Addresses → New Address") for quick return to the list.
- **Footer**: "© Komunalka. All rights reserved."

### Mobile Version

On screens narrower than desktop, the sidebar is hidden and opens via a hamburger button in the top bar as a slide-out panel with a dimmed overlay (closes with the Escape key or by clicking outside it). At the bottom of the mobile menu is a user block with "My Profile", "Settings", "Log Out" items.

### Theme

The theme switcher in the top bar has three modes: **Light / Dark / System** (follows the device settings). The choice is also available under "Settings → Appearance".

## 2.2 Dashboard (Home Page)

The dashboard is an analytical overview of all your addresses. It consists of the following blocks:

### Greeting
"Welcome, {name}! Here's an overview of your utilities for {month year}." On the right:
- an **"Account Address"** dropdown (appears only once addresses have been created). Important: in the current version this selector **does not filter the dashboard data** — all statistics (chart, readings, expenses) are always aggregated across all your addresses;
- an **"Add Reading"** button — leads to the reading submission page.

### Consumption Chart
A line chart of monthly consumption, with a separate line for each utility type (electricity, gas, water, etc.). Period switcher: **Year / 6 Months / 3 Months** (default: 6 months).

### Recent Readings
The last 10 readings across all addresses. On desktop — a table (Service / Date / Reading / Consumption); on mobile — cards (with the meter name). Consumption is highlighted: positive — green, negative — red, zero — gray. Units of measurement are shown in international format (kWh, m³, Gcal).

### Expense Breakdown
A donut chart of expense breakdown by utility type, with the total amount in hryvnias in the center and a legend showing amounts per service. Expenses are calculated only for readings linked to a tariff (i.e. when the meter has a provider with tariffs assigned).

### Quick Actions
Three buttons: **"Add Reading"**, **"Add Address"**, **"View Tariffs"**.

> For a new user with no data, the charts and tables will be empty — start with "Add Address" (see [recommended sequence of steps](README.md#quick-start-recommended-order-of-actions)).
