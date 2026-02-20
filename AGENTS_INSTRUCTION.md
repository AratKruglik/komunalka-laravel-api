# AGENTS.md Standard Instruction Guidelines

*For AI Coding Agents like Codex, Cursor, Copilot & more*

---

## 1. Purpose & Scope

This document is a reusable **prompt guide** for authoring `AGENTS.md` files in software projects that use AI coding agents (OpenAI Codex, Cursor, GitHub Copilot coding agent, Aider, Gemini CLI, etc.).

`AGENTS.md` is an **open, tool-agnostic standard**: it’s just Markdown, and it’s supported by a growing ecosystem of agents and developer tools.

You can drop this file into any repository as:

* A **single source of truth** for persistent instructions to agents.
* A **complement** to `README.md`: README is for humans; `AGENTS.md` is for automated collaborators.

---

## 2. What is AGENTS.md?

At its core:

* **Format:** Just Markdown; no special syntax or required fields. Any headings are allowed.
* **Role:** A predictable “**README for agents**” – where tools look for project-specific instructions, commands, and constraints before doing any work.
* **Ecosystem:** Supported (directly or via documented integrations) by Codex, Cursor, Aider, Gemini CLI, GitHub Copilot’s coding agent, and many others.
* **Governance:** Stewarded as an open community standard under the Agentic AI Foundation (AAIF), alongside MCP and Goose.

Think of it as telling a diligent new teammate:

> “Here’s exactly how this repo works, and here’s how I expect you to behave.”

…except the teammate is an AI coding agent.

---

## 3. Core Design Principles

When you design your `AGENTS.md`, keep these principles in mind.

### 3.1 Tool-agnostic & neutral

* Avoid tool-specific prompt tricks (“You are Cursor…”) unless strictly necessary.
* Prefer neutral language that any agent can interpret:

  * “Always run `npm test` after modifying backend code.”
  * “Prefer functional React components over class components.”
* This ensures one file works across Codex, Cursor, Copilot, Aider, Gemini CLI, etc.

### 3.2 “Closest file wins”

Most implementations use a **proximity rule**:

* The **nearest** `AGENTS.md` to the file being edited takes precedence if there are multiple in the tree.
* Some tools (like Codex) also merge global + project docs, but directories closer to your current working path override higher-level guidance.

Design your rules assuming:

> Local instructions override global ones, and explicit user prompts override everything.

### 3.3 Layered, living documentation

* Treat `AGENTS.md` as a **living document** that evolves with your project and agents.
* Start small; iterate based on what agents get wrong or need clarification on.
* Store it in Git and review changes like any other code.

### 3.4 Make instructions executable

Agents can often **run shell commands** they find in `AGENTS.md`:

* If you list build / test commands, many agents will run them automatically to validate work.
* Prefer concrete, copy-pastable commands in fenced code blocks.

Example:

````markdown
## Tests

- Run all tests:

  ```bash
  pnpm test
````

* Run backend tests only:

  ```bash
  pnpm test --filter backend
  ```

````

### 3.5 Clear > Clever

Small, crystal-clear rules outperform long, fuzzy essays.

Good patterns:

- “**Always** do X before Y.”
- “**Never** modify files in Z directly; use generated code.”
- Short bullet lists of **do / don’t** rules.
- Concrete examples of **good vs bad output**.

---

## 4. File Placement & Naming

### 4.1 Recommended layout

**For most repos:**

- Place a primary `AGENTS.md` at the **repository root**.  
- For large monorepos or distinct services, add **nested `AGENTS.md` files** inside subpackages (`apps/web/AGENTS.md`, `services/payments/AGENTS.md`, etc.).

Agents will typically use:

- Root `AGENTS.md` for **global project rules**.
- Nested files for **local overrides** (e.g. a different test command for the payments service).

### 4.2 Codex-style global vs project docs

Codex uses a discovery order like:

1. **Global scope** (user machine)
   - `~/.codex/AGENTS.override.md` (if present)  
   - Else `~/.codex/AGENTS.md`  
2. **Project scope** (repo → current directory)
   - In each directory, use the first of:
     - `AGENTS.override.md`
     - `AGENTS.md`
     - Any configured fallback names (e.g. `project_doc_fallback_filenames` in `config.toml`).

Guideline:

- Use **global files** for personal habits (editor, keyboard preferences, etc.).
- Use **repository `AGENTS.md`** for project-specific contracts (tests, security, architecture).

### 4.3 Cursor & other tools

Cursor’s model is similar but adds **Rules** (user/team/project) in addition to `AGENTS.md`: rules are persistent context; they’re stored in files, versioned, and can be scoped to directories or file globs.

Guideline:

- Put **project rules that should apply across tools** (build, test, style, security, domain logic) in `AGENTS.md`.
- Put **personal or editor-only rules** (e.g. “use my TODO format”) in Cursor’s Rule files (e.g. `.cursor/rules/*.mdc`).

### 4.4 Fallback & migration

You can migrate existing docs by renaming and symlinking, for example:

```bash
mv AGENT.md AGENTS.md && ln -s AGENTS.md AGENT.md
````

Many tools can be configured to treat `AGENTS.md` as their context file.

---

## 5. Recommended Structure & Sections

There are **no required sections**, but common patterns have emerged across many repos.

Below is a recommended structure tuned for AI coding agents.

### 5.1 Header & project intro

Start with a simple heading and a short purpose:

```markdown
# AGENTS.md

This file defines how AI coding agents should work in this repository.
```

Then include a **brief overview**:

* What this repo is (e.g. “TypeScript monorepo for our SaaS product”).
* Main subprojects (APIs, frontends, infra).
* Where to start (entrypoints, key directories).

### 5.2 Repository map & tech stack

Help the agent navigate:

* High-level directory map
* Key technologies

Example:

```markdown
## Repository structure

- `apps/web` – Next.js frontend
- `apps/api` – FastAPI backend
- `packages/ui` – Shared React component library
- `infra/` – Terraform and deployment scripts

## Tech stack

- Frontend: React + Next.js + TypeScript
- Backend: Python + FastAPI
- Database: PostgreSQL (via Prisma)
- Package manager: pnpm
```

### 5.3 Setup & environment

This is where many tasks start:

* Installation commands
* Env variables and secrets
* Seed/migration commands

Example:

````markdown
## Setup

- Install dependencies:

  ```bash
  pnpm install
````

* Copy environment file and fill values:

  ```bash
  cp .env.example .env
  ```

* Apply database migrations:

  ```bash
  pnpm prisma migrate dev
  ```

````

### 5.4 Build, run & tasks

Give one clear way to:

- Run the dev server
- Build for production
- Run background workers / schedulers

Example:

```markdown
## Running the project

- Start everything locally:

  ```bash
  pnpm dev
````

* Start only the API:

  ```bash
  pnpm dev:api
  ```

* Start only the frontend:

  ```bash
  pnpm dev:web
  ```

````

### 5.5 Testing strategy

Agents rely heavily on this section; many tools will attempt to run tests you list here.

Include:

- **Default test command** (what must pass before merging).
- **Scoped tests** (per package, unit vs integration).
- How to run **lint** and **type checks**.

Example:

```markdown
## Tests

- Run the full local test suite:

  ```bash
  pnpm test
````

* Run frontend tests only:

  ```bash
  pnpm test:web
  ```

* Run backend tests only:

  ```bash
  pnpm test:api
  ```

* Lint & type-check:

  ```bash
  pnpm lint
  pnpm typecheck
  ```

**Expectation:** After making changes, ensure `pnpm test` and `pnpm lint` both succeed before you consider a task complete.

````

### 5.6 Code style, linting & formatting

Agents need to know how to **shape code**:

- Language features you prefer/avoid.
- Linting/formatting tools (ESLint, Prettier, Black, etc.).
- Small but clear preferences (quotes, semicolons, naming conventions).

Example:

```markdown
## Code style

- TypeScript `strict` mode is enabled; fix all type errors.
- Use single quotes in JavaScript/TypeScript, no semicolons.
- Prefer functional React components and hooks over class components.
- Use async/await, not raw Promise chains.
- Follow existing naming patterns in the surrounding code.

## Linting & formatting

- Run:

  ```bash
  pnpm lint
  pnpm format
````

* Do not disable lint rules unless there is a strong reason and an explanatory comment.

````

### 5.7 Architecture & design rules

Describe **how the system is structured** and what patterns must be preserved:

- Layer boundaries (API, domain, infra).
- Where business logic should (and should not) live.
- Rules for imports between modules.

Example:

```markdown
## Architecture rules

- Keep business logic in the `domain/` layer, not in controllers or React components.
- API handlers (`apps/api/routes/*`) may only call:
  - DTO mappers (`apps/api/mappers/*`)
  - Domain services (`packages/domain/*`)
- React components in `packages/ui` must be:
  - Framework-agnostic (no Next.js `useRouter`, etc.)
  - Presentational only (no direct data fetching).
````

### 5.8 Domain & business constraints

This is where you encode **business rules** that an agent can easily violate:

* Pricing rules
* Permission / role rules
* Regulatory constraints

Example:

```markdown
## Domain rules

- A trial user can have at most 1 active workspace.
- Only admins may modify billing settings.
- Do not change tax calculation logic in `packages/billing/tax/*` without explicit instruction in the task.
```

### 5.9 Security, privacy & compliance

Agents will blindly follow instructions unless you constrain them. Use this section to define red lines:

```markdown
## Security & privacy

- Never hard-code secrets or tokens. Use environment variables and `.env` files (which are gitignored).
- Do not log full access tokens, passwords, or secrets. Mask credentials in logs.
- Before making changes in `auth/` or `billing/`, clearly describe what you're changing in the PR description.
- When handling personal data, only store fields that are strictly necessary.
```

This is especially important when multiple tools and teams rely on the same repo.

### 5.10 Git, branch & PR workflow

Guidance for **how agents should commit and open PRs**, especially relevant for Codex, Copilot coding agents and other automated tools that can stage changes.

Example:

```markdown
## Git & PR workflow

- Branch naming: `feature/<short-kebab-name>` or `fix/<short-kebab-name>`.
- Commit messages: Use the imperative mood, e.g. `fix: handle null customer id`.
- Before opening a PR:
  - Ensure tests and lint pass.
  - Update or add tests for behavior changes.
- PR titles: `[scope] Short description` (e.g. `[billing] Handle trial expiry emails`).
- PR description should include:
  - Summary of the change
  - How it was tested
  - Any migrations or follow-up steps
```

### 5.11 Non-goals & “do not touch” areas

Agents are very willing to help; sometimes you want them **not** to help.

Example:

```markdown
## Do not touch

- Do not modify files under `infra/` unless explicitly asked.
- Do not refactor `legacy/` code without a clear task.
- Do not introduce new external dependencies without explicit instruction.
```

### 5.12 Tool-specific notes (optional)

If absolutely necessary, you can include short tool-specific sections, but keep most content generic.

```markdown
## Tool-specific notes

### For Codex

- Global instructions live in `~/.codex/AGENTS.md`.
- This file is the project-specific source of truth; avoid duplicating rules globally.

### For Cursor

- Project-wide rules are defined here.
- User-specific preferences should live in your personal Cursor Rules, not in this file.
```

---

## 6. Writing Good Instructions (Prompt Patterns)

Think of `AGENTS.md` as a **persistent system prompt** for your repo.

### 6.1 Use imperative, testable rules

* “Run `pnpm test` and `pnpm lint` before considering a change complete.”
* “When modifying TypeScript files, fix all type errors; do not ignore TypeScript diagnostics.”
* “If you create a new API endpoint, add end-to-end tests.”

Avoid:

* “We usually try to keep tests green.” (Vague)
* “Make sure everything works well.” (Unverifiable)

### 6.2 Prefer “Do / Don’t” lists

Short lists of **dos and don’ts** are easier for models to follow consistently.

Example:

```markdown
## Styling: do & don't

**Do**

- Use our shared UI components from `packages/ui/*`.
- Keep React components under 200 lines when possible.
- Extract reusable logic into hooks in `packages/ui/hooks/*`.

**Don't**

- Don't introduce inline styles; use the existing Tailwind/SCSS utilities.
- Don't use `any` unless you explain why in a comment.
- Don't add new state management libraries; use the existing Redux Toolkit setup.
```

### 6.3 Give examples of good output

Where helpful, show a short **before/after** or “good vs bad” code snippet.

Example:

````markdown
### Example: Good service function

```ts
// GOOD: domain logic in service, clear return type
export async function createSubscription(input: CreateSubscriptionInput): Promise<Subscription> {
  // ...
}
````

### Example: Avoid this

```ts
// AVOID: mixing HTTP concerns with business logic
export async function createSubscriptionHandler(req, res) {
  // heavy business logic here...
}
```

This makes it much more likely the agent imitates your preferred style.

### 6.4 Encode workflows, not just facts

Agents respond well to **step-by-step workflows**.

Example:

```
## When implementing a new API endpoint

1. Add the route in `apps/api/routes/*`.
2. Implement the handler using a domain service from `packages/domain/*`.
3. Add or update validation schemas in `packages/domain/schemas/*`.
4. Add tests in `apps/api/tests/*`.
5. Run:
   pnpm test:api
```

---

## 7. Multi-Agent Compatibility Notes

### 7.1 Codex

Codex reads `AGENTS.md` **before doing any work**, merging global and project-specific instructions so every task starts from consistent expectations.

Guidelines for Codex:

- Keep **personal defaults** in `~/.codex/AGENTS.md`.
- Keep **project rules** in repo `AGENTS.md` files.
- Use nested `AGENTS.md` (or `AGENTS.override.md`) to specialize rules for subtrees.
- Use Codex CLI to sanity-check:

  ```bash
  codex --ask-for-approval never "Summarize the current instructions you loaded."
  ```

### 7.2 Cursor

Cursor uses a combination of:

* **User / Team / Project Rules** (separate rule files) and
* Project docs like `AGENTS.md`.

Guidelines:

* Use `AGENTS.md` for **shared, tool-agnostic project rules**.
* Use Cursor rule files (e.g. `.cursor/rules/*.mdc`) for **editor-specific behaviors** and deep IDE behavior tweaks.
* Avoid duplicating the same rule in both; duplication can cause conflicting guidance.

### 7.3 GitHub Copilot coding agent

GitHub’s **Copilot coding agent** reads `AGENTS.md` to understand how to **build, test, and validate** changes in a repo.

Guidelines:

* Make sure your **test and build commands** are correct and fast enough to run regularly.
* Clearly mark any **expensive** operations (e.g. “load tests” or large migrations) and indicate when to skip them.

### 7.4 Other tools (eg. VS Code, Gemini CLI, etc.)

`AGENTS.md` is intentionally designed so one file works across many tools.

* Aider, Gemini CLI and others can be configured to read `AGENTS.md` as their primary context file.
* Keep instructions **generic** and **shell-command based** so any agent that can run commands can obey them.

---

## 8. Iteration Checklist

Use this checklist to keep your `AGENTS.md` healthy over time:

- [ ] Does it live at the repo root (and in key subprojects where needed)?  
- [ ] Does it tell an agent **how to set up, run, and test** the project quickly?  
- [ ] Does it encode your **strong opinions** (do/don’ts) in clear, imperative language?  
- [ ] Are there **example commands** that agents can safely run?  
- [ ] Are **dangerous or sensitive areas** clearly marked as “do not touch”?  
- [ ] Are you avoiding duplication with Cursor rules, Codex global docs, etc.?  
- [ ] Have you run an agent against the repo and updated `AGENTS.md` based on what went wrong?  

