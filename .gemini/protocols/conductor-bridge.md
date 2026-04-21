# Protocol: Conductor Bridge for Gemini CLI

This protocol ensures seamless integration between Conductor's track management and the Agentic SDLC pipelines defined in `@.gemini/rules/workflow.md`.

## 1. Track Ownership
- Every major directive MUST have a dedicated Track in `conductor/tracks/`.
- The **BA Agent** is the primary creator of Tracks.
- The **Docs-Writer Agent** is the primary closer of Tracks.

## 2. Pipeline Markers
The `spec.md` of every track MUST include a `Pipeline:` field under the Overview or as metadata:
- `Pipeline: Standard Feature` -> `ba → ddd-architect? → developer → QG → docs-writer`
- `Pipeline: Bug Fix` -> `debugger → developer → verify`
- `Pipeline: CI/CD` -> `devops/ci-cd-engineer → QG`

## 3. Plan-Driven Execution
- Agents MUST read the active Track's `plan.md` BEFORE starting any task.
- Tasks in `plan.md` MUST use the format: `- [ ] Task: <AgentName> - <Action>`.
- When an Agent starts a task, it MUST change `[ ]` to `[~]`.
- When an Agent finishes a task, it MUST change `[~]` to `[x]` and append the short commit SHA if a commit was made.

## 4. Phase Transition Protocol
When a phase in `plan.md` is completed:
1. The finishing Agent MUST report "Phase X Complete" to the Orchestrator.
2. The Orchestrator MUST verify the "Quality Gates" defined in `conductor/workflow.md`.
3. The Orchestrator triggers the next Agent(s) based on the `Pipeline` type.

## 5. Metadata Sync
- `conductor/tracks.md` MUST be kept in sync with the current status of all tracks.
- Every commit related to a track task MUST follow the message convention: `conductor(<track-id>): <description>`.

## 6. Context Management
To minimize context usage, Agents should ONLY load rules relevant to their current Pipeline phase.
- BA/Architect -> `architecture.md`, `workflow.md`
- Developer -> `code-style.md`, `forms-authorization.md`, `inertia-react.md`
- Quality Gate -> `testing.md`, `mcp-stack.md`
