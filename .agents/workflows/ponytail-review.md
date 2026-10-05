---
description: "Review code specifically to hunt over-engineering, dead abstractions, unnecessary libraries, and bloat"
---

# /ponytail-review Workflow

Review the current diff or files exclusively for unnecessary complexity:

- Format findings: `L<line>: <tag> <what>. <replacement>.`
- Tags:
  - `delete:` dead code, speculative features
  - `stdlib:` custom code where standard library has built-in
  - `native:` JS/lib bloat where browser HTML/CSS/DOM already covers it
  - `yagni:` abstractions with only 1 caller or implementation
  - `shrink:` same logic in fewer lines
- End with: `net: -<N> lines possible.`
- If already lean: `Lean already. Ship.`
