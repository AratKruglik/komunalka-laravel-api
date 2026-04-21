# Plan: Conductor-Driven SDLC Orchestration

Transform the current multi-agent setup into a track-driven workflow where Conductor serves as the source of truth for task execution and agent synchronization.

## Goal
Automate the SDLC pipelines defined in `@.gemini/rules/workflow.md` using Conductor tracks, ensuring automatic agent spawning and plan synchronization.

## Key Changes

### 1. Shared Protocols (`.gemini/protocols/`)
- **conductor-bridge.md**: Define rules for track management, task status (`[ ]`, `[~]`, `[x]`), and commit linking.
- **high-signal-output.md**: Global standard for concise, engineer-to-engineer communication to save context.

### 2. Agent Updates (`.gemini/agents/`)
- **ba.md**: Transform into the Track Architect. It must create the track directory, `spec.md` (with pipeline type), and `plan.md`.
- **developer.md**: Update to be track-aware, picking tasks from `plan.md` and updating status.
- **reviewer.md / tester.md / security-scanner.md**: Update to work in the "Quality Gate" phase of a track.

### 3. Workflow Alignment (`.gemini/rules/workflow.md`)
- Map pipeline phases directly to Conductor track phases.
- Define triggers for "Quality Gate" fan-out within Conductor.

### 4. Automation Commands (`.gemini/commands/`)
- **/start**: Command for the BA agent to initialize a new track for a given description.
- **/qa**: Parallel execution of the Quality Gate (fan-out: tester, reviewer, security, qa).
- **/ship**: Finalization command for docs-writer to create PRs and close tracks.

## Implementation Steps

### Phase 1: Bridge & BA (The Foundation)
- [ ] Create `.gemini/protocols/conductor-bridge.md`.
- [ ] Update `.gemini/agents/ba.md` with track creation logic.
- [ ] Update `.gemini/rules/workflow.md` to reference Conductor tracks.

### Phase 2: Execution Agents
- [ ] Update `developer.md` with track-aware task management.
- [ ] Update `ddd-architect.md` to document decisions in `tracks/<id>/spec.md`.

### Phase 3: Automation Layer
- [ ] Create `.gemini/commands/start-track.toml`.
- [ ] Create `.gemini/commands/qa-gate.toml`.
- [ ] Create `.gemini/commands/ship.toml`.

### Phase 4: Context Optimization
- [ ] Remove forced rule imports from `GEMINI.md`.
- [ ] Implement selective rule loading in agent definitions.

## Verification
- [ ] Run `/start "Implement new user dashboard"` -> Verify track creation.
- [ ] Run `/qa` -> Verify parallel execution and consolidation in `plan.md`.
- [ ] Run `/ship` -> Verify PR creation and track closure.
