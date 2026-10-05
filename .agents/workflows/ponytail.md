---
description: "Activate Ponytail lazy senior dev mode - enforce YAGNI, standard library first, native platform features, and minimum clean code"
---

# /ponytail Workflow

Activate the **Ponytail** engineering philosophy:

1. **The Ladder**:
   - Rung 1: Does this need to exist? (YAGNI -> skip it)
   - Rung 2: Already in this codebase? (Reuse helper/util/pattern)
   - Rung 3: Stdlib does it? (Use standard library)
   - Rung 4: Native platform feature? (`<input type="date">`, native CSS/HTML, DB constraints)
   - Rung 5: Already-installed dependency? (Use what exists)
   - Rung 6: Can it be one line? (One line)
   - Rung 7: Only then, minimum working code.

2. **Rules**:
   - No unrequested abstractions.
   - Deletion over addition.
   - Shortest working diff wins.
   - Fix root cause, not symptom (grep all callers).
   - Never compromise on input validation at trust boundaries, security, or accessibility.
