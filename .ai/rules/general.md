---
paths:
  - docker-compose.yaml
---

# General

## App runs in Compose with DB_HOST=db
Laravel app is containerized via Dockerfile + docker-compose `app` service. Compose overrides DB_HOST to `db` for the app container; keep DB_HOST=127.0.0.1 in .env when running PHP on the host against Compose MySQL. Start with `docker compose up -d --build`. App on :8000, Adminer on :8080.
