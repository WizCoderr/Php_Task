---
paths:
  - 'app/Http/Controllers/Api/**'
---

# Api

## API auth uses Sanctum tokens
API authentication is Sanctum personal access tokens (auth:sanctum). Public: POST /api/register, POST /api/login. Protected: logout, /user, and all task CRUD. Tasks are scoped to $request->user(); other users' tasks return 404 via TaskService, not 403.
