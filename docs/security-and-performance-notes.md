# Security And Performance Notes

This page captures framework-level hardening that module authors should preserve.

## Menu Module

- Menu links are normalized before save. Empty links become `#`; protocol links such as `javascript:`, `data:`, and protocol-relative URLs are rejected.
- Menu listing now loads the tree in one query and groups rows in PHP. Avoid reintroducing per-parent child queries because large menu trees will generate N+1 database load.
- Parent position dropdown data uses grouped `MAX(int_position)` queries instead of one query per parent.

## Permission Module

- Permission APIs should validate that the decoded JSON body is an object before reading fields.
- Permission assignment payloads are capped to avoid very large request bodies creating excessive insert loops.
- Dynamic table/domain inputs must pass strict identifier validation before being used in model lookup helpers.
- Mutating permission endpoints must have permission guards. Use `assign_permissions` for assignment flows and more specific permissions, such as `add_permission`, for creation flows.
- Permission write services should use the model's active database connection so transaction boundaries cover all related delete and insert statements.

## RBAC Queries

Group membership lookups must not be concatenated directly into `IN (...)` SQL. Build placeholders and bind each group ID. This keeps the query safe even if a legacy table contains malformed data.

## Lightweight Checks

Use:

```bash
php oryn doctor
```

for deployment readiness checks that should not touch the database. Use full migrations and application tests separately when the target database is available.
