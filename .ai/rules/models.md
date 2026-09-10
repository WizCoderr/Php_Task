---
paths:
  - 'app/Models/*.php'
---

# Models

## Tasks are owned by user_id
Task belongs to User via user_id (FK cascadeOnDelete). Always create/query tasks through the owning user (User::tasks() / TaskService scoped to User). Cross-user access returns 404.
