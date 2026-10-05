# Gemini Agent Workspace Instructions

This repository is equipped with **ECC (Engineering Core & Capabilities)** and **Ponytail (Lazy Senior Dev)** agent skills and workflows to build high-performance, professional web applications.

---

## 1. Core Engineering Principles

### The Ponytail Ladder (Anti-Bloat & Efficiency)
Stop at the first rung that holds:
1. **Does this need to exist at all?** (YAGNI -> Skip speculative features)
2. **Already in this codebase?** (Reuse existing helpers, components, and patterns)
3. **Does the standard library / browser native do this?** (Native `<dialog>`, `<input type="date">`, `Intl`, Fetch API, native CSS Grid/Flexbox)
4. **Does an already-installed dependency solve it?** (Never install a library for what a few lines of code can do)
5. **Can it be one line?** (One line)
6. **Only then:** Write the minimum necessary, clean code.

> **Never compromise on:** Trust-boundary input validation, data integrity, security, accessibility (a11y), and visual excellence.

### Bug Fix Rule
Always fix the **root cause**, not just the reported symptom. Grep all callers of the affected function and apply the guard/fix at the source.

---

## 2. Professional Web Application Standards

When building web applications in this repository:
- **Visual Excellence**: Clean hierarchy, cohesive color tokens (HSL tailored), modern typography (Inter, Plus Jakarta Sans, Roboto), subtle micro-animations, and responsive layouts.
- **Native Platform First**: Leverage modern HTML5 and Vanilla CSS features before reaching for external dependencies.
- **Security & Integrity**: Sanitize inputs, use prepared statements (prevent SQL injection), validate at trust boundaries, and safeguard session handling.
- **Verification**: Ensure every significant feature has an automated check or runnable verification step (`tdd-workflow`, `browser-qa`).

---

## 3. Installed Skills & Tooling Directory

All skills are available under `.agents/skills/<skill-name>/SKILL.md`:

### 🦹‍♂️ Ponytail Skills (Code Minimization & Architecture)
- `ponytail`: Enforces the simplest, laziest working solution (supports `lite`, `full`, `ultra`).
- `ponytail-review`: Specialized review hunting over-engineering, dead code, and bloated dependencies.
- `ponytail-audit`: Audits files and dependencies for bloat and potential line reductions.
- `ponytail-debt`: Identifies architectural complexity and technical debt.
- `ponytail-gain`: Quantifies lines saved and complexity reduced.

### 🎨 UI/UX & Frontend Skills (ECC)
- `design-system`: Establishes cohesive tokens, color systems, typography, and spacing.
- `make-interfaces-feel-better`: Adds micro-interactions, responsive states, transitions, and UX polish.
- `dashboard-builder`: Best practices for data visualization, stats cards, filtering, and data tables.
- `liquid-glass-design`: Premium glassmorphism, depth, and modern visual styling.
- `frontend-patterns`: Component organization, state management, and modern CSS patterns.
- `frontend-design-direction`: Guidance on aesthetic choices, visual hierarchy, and theme design.
- `taste` & `taste-application`: High-standard visual and interaction critique.

### ⚙️ Backend, Database & API Skills (ECC)
- `backend-patterns`: Resilient server architecture and controller design.
- `api-design`: Clean, RESTful endpoint design, error formatting, and response schemas.
- `mysql-patterns`: Efficient query design, indexing, and relational data modeling.
- `database-migrations`: Schema versioning, rollbacks, and data consistency.
- `laravel-patterns` & `laravel-security`: Laravel best practices and security hardening.

### 🛡️ Quality, Security & Testing Skills (ECC)
- `tdd-workflow`: Test-driven development loop (Red -> Green -> Refactor).
- `verification-loop`: Automated feedback loop to test and verify changes.
- `browser-qa`: Browser testing and DOM validation.
- `security-review`: Comprehensive code auditing for vulnerabilities (OWASP, injection, auth).
- `security-scan`: Scans permissions, configs, and dependencies.

---

## 4. Workflows (Slash Commands)

Quick workflows in `.agents/workflows/`:
- `/plan`: Step-by-step feature planning before writing code.
- `/ponytail`: Re-align code with the Ponytail ladder and minimal footprint.
- `/ponytail-review`: Hunt complexity and report lines that can be cut.
- `/code-review`: Fresh-context code review for quality and correctness.
- `/build-fix`: Systematic error diagnosis and repair.
- `/refactor-clean`: Clean up technical debt and dead abstractions.
- `/security-scan`: Audit project security posture.
