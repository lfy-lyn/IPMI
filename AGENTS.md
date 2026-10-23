# IPMI — AI Agent Instructions

## 1. Project Overview

IPMI is an organizational information system consisting of:

- A public organizational profile website.
- An internal application for members and administrators.
- Membership applications and account activation.
- Member, division, and organizational position management.
- Programs, activity sessions, and attendance.
- Division learning materials.
- Internal announcements.
- Audit logs.

The project must be developed incrementally, with security,
maintainability, and correctness as priorities.

## 2. Required Reading

Before implementing any feature, read the relevant documents:

1. `IPMI_project_spec_v1.1.md`
2. `docs/decisions.md`, if available.
3. `docs/authorization_matrix.md`, if available.
4. `docs/architecture.md`, if available.
5. `docs/database_design.md`, if available.
6. `docs/implementation_plan.md`, if available.

Do not invent requirements when the documentation is incomplete.

If a requirement is ambiguous and affects business logic,
security, database structure, or permissions, ask the user first.

## 3. Technology Stack

- Laravel 13
- PHP
- Blade
- Livewire
- Tailwind CSS
- MySQL

Do not introduce Filament or another major framework unless
the user explicitly approves the change.

Verify installed versions and existing configuration before
assuming that a dependency or feature is available.

## 4. Development Rules

- Work incrementally, one task at a time.
- Inspect relevant existing files before editing them.
- Do not assume the project is empty or that previous code is correct.
- Do not rewrite unrelated files.
- Do not generate large amounts of code without explaining the plan.
- Prefer Laravel conventions and simple, maintainable solutions.
- Use migrations for database schema changes.
- Use database transactions when multiple operations must remain consistent.
- Avoid unnecessary dependencies and duplicated business logic.
- Do not modify project requirements without approval.

## 5. Security Rules

- Enforce authorization on the backend.
- Never rely only on hidden buttons or frontend restrictions.
- Prevent unauthorized access to other members' data.
- Enforce division boundaries where required.
- Prevent users from assigning themselves higher permissions.
- Validate all user input on the server.
- Protect authentication and account activation flows.
- Store passwords using secure password hashing.
- Restrict uploads by file type and size.
- Keep private files inaccessible without authorization.
- Never expose secrets, passwords, or activation tokens in logs.
- Record important administrative actions in audit logs.

## 6. Role and Permission Rules

Organizational positions are separate from application roles.

Do not assume that holding an organizational position
automatically grants every system permission.

Follow the project's authorization matrix when available.

Never allow a Division Head to manage another division's data
unless the documented permissions explicitly allow it.

Never allow ordinary members to modify their own system roles.

## 7. Database Rules

Before creating or changing database tables:

1. Read the project specification.
2. Review the existing migrations and models.
3. Check relationships and data ownership.
4. Identify relevant constraints and indexes.
5. Explain significant design decisions.
6. Wait for approval when the change affects unresolved requirements.

Do not rename or delete existing tables without inspecting
their dependencies and obtaining approval.

Preserve historical records when accounts are deactivated.

## 8. Testing Rules

- Run relevant tests after implementation.
- Add tests for important business rules.
- Test both permitted and forbidden actions.
- Test division-level access restrictions.
- Test membership approval and activation flows.
- Test important database operations and failure cases.
- Do not claim a feature is complete if it has not been verified.
- Report failed tests and unresolved issues honestly.

## 9. Working Procedure

For each task:

1. Explain the objective briefly.
2. Inspect relevant files.
3. Identify the proposed changes.
4. Implement only the approved scope.
5. Run relevant tests or verification commands.
6. Report changed files and test results.
7. Identify remaining issues or decisions.

If the task is too large, divide it into smaller tasks.

Do not proceed with destructive operations without explicit approval.

## 10. Git Rules

- Inspect Git status before making changes.
- Do not overwrite unrelated user changes.
- Do not discard user work without permission.
- Do not commit or push changes unless explicitly requested.
- Prefer small, understandable changes.

## 11. Scope Control

Features excluded from the initial MVP include:

- Finance management.
- Event participant registration.
- QR attendance.
- WhatsApp API integration.
- Public profile CMS.
- Specialized Intelektual features.
- Organizational position history.

Do not implement excluded features unless the user approves
a scope change.

## 12. Communication

Communicate with the user in Indonesian unless requested otherwise.

Explain technical decisions clearly and concisely.

When reporting an error:

- Show the relevant error.
- Explain the likely cause.
- Verify the cause before applying a fix when possible.
- Explain the proposed solution.

Do not fabricate file contents, test results, command outputs,
or implementation status.
