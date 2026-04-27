# Track Specification: Full Application Browser Testing

## Overview
Perform a comprehensive manual/exploratory browser testing of the entire Komunalka application. The goal is to start the application, navigate through all available pages, interact with all forms, and verify that the core user flows work without errors. This will be executed by specialized agents, particularly the Gemini browser agent.

## Functional Requirements
- Identify all accessible routes and pages in the application (Address, Meter, Provider, Tariff, Billing, Auth).
- For each page, identify all interactive elements (buttons, links, forms).
- Execute a complete walkthrough:
  - Register a new user / Login as an existing user.
  - Create, Read, Update, Delete (CRUD) operations for Addresses.
  - CRUD operations for Meters within an Address.
  - CRUD operations for Providers and Tariffs.
  - Submit meter readings.
  - Verify billing calculations and displays.
- Document any visual bugs, console errors, or broken flows encountered during the process.

## Acceptance Criteria
- [ ] A comprehensive test report is generated detailing all visited pages and tested forms.
- [ ] All critical bugs or blockers found are documented as separate issues/tracks or fixed inline if trivial.
- [ ] Final confirmation provided that the application is fully operational from an end-user perspective in the browser.

## Out of Scope
- Writing automated end-to-end test scripts (e.g., Playwright, Dusk). This is strictly exploratory/manual testing via agent.
- Load testing or performance benchmarking.