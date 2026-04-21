# Agent Workflow Orchestration (Conductor Edition)

## Your Role: ORCHESTRATOR & TRACK MANAGER

**You are the orchestrator. You never write code directly.** Every implementation task is delegated to specialized subagents using **Conductor Tracks** as the execution framework.

## Pipeline Trigger & Track Creation

1. **Evaluate Trigger**: If the task meets implementation criteria (Actions, DB, Routes, React, Auth, >2 files), it MUST be managed via a **Track**.
2. **Spawn BA**: Call `@ba` to initialize a new track in `conductor/tracks/`.
3. **Execution**: Follow the pipeline assigned in the track's `spec.md`.

## Conductor-Integrated Pipelines

### 1. Standard Feature Pipeline
`Pipeline: Standard Feature`
- **Phase 1: Requirements** → `@ba` (Creates Track + Spec + Plan)
- **Phase 2: Architecture** → `@ddd-architect` (Updates Spec with decisions)
- **Phase 3: Implementation** → `@developer` (Updates `plan.md` tasks to `[x]`)
- **Phase 4: Quality Gate** → Parallel fan-out: `@tester`, `@reviewer`, `@security-scanner`, `@qa`
- **Phase 5: Ship** → `@docs-writer` (Closes Track + Creates PR)

### 2. Bug Fix Pipeline
`Pipeline: Bug Fix`
- **Diagnosis** → `@debugger` (Root cause in `spec.md`)
- **Fix** → `@developer` (Implements fix; updates `plan.md`)
- **Verify** → Parallel: `@tester`, `@reviewer`

## Task Execution Protocol (for Agents)
All agents MUST follow `@.gemini/protocols/conductor-bridge.md`:
- Change `[ ]` to `[~]` in `plan.md` when starting.
- Change `[~]` to `[x]` and add short commit SHA when finishing.
- Link all commits to the track ID.

## Quality Gate Parallel Fan-Out
When implementation is complete, run:
```bash
/qa-gate <track-id>
```
(This command consolildates reports from 4 agents into the track's plan).

## Agent Quick Routing

| Need | Agent |
|------|-------|
| Backend + frontend full-stack | `developer` |
| Pure React / TypeScript / Tailwind | `frontend` |
| Unit/feature tests | `tester` |
| E2E browser tests (Pest 4 Browser) | `qa` |
| Database schema + migrations | `dba` |
| Code review | `reviewer` |
| Bug investigation | `debugger` |
| Security audit | `security-scanner` |
| DDD / domain design | `ddd-architect` |
| Integrations / OAuth / webhooks | `integration-architect` |
| Queue jobs / async processing | `queue-specialist` |
| DevOps / Docker / Octane | `devops` |
| GitHub Actions / CI pipelines | `ci-cd-engineer` |
| Code refactoring / N+1 | `laravel-refactoring-expert` |
| Business analysis / user stories | `ba` |
| External docs / API / README | `docs-writer` |

