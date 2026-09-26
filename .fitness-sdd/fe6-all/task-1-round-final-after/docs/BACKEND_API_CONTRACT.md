# Smart Fitness Backend API Contract

## 1. Conventions

- Base URL: `/api`; JSON request/response; UTF-8.
- Protected routes use `Authorization: Bearer <token>` and revalidate active Account/Role from Database.
- Role middleware values: `MEMBER`, `PT`, `RECEPTIONIST`, `ADMIN`.
- Resource IDs come from the authenticated principal or verified ownership scope. Foreign resources are generally concealed with `404`.
- Validation failures return `422`; unauthenticated `401`; forbidden `403`; state/idempotency conflict `409`; quota exhaustion `429`; provider/runtime errors `502/503`.
- Workflow errors use `{ "message": "...", "code": "STABLE_CODE" }`. Validation responses follow Laravel JSON validation format.
- Endpoints marked **Idempotency-Key** require a UUID header. Reusing a key with the same canonical payload returns the original result; a different payload returns `409`.
- All server timestamps are ISO-8601 in API responses; database timestamps retain microseconds. Business date/timezone rules remain those in `PROJECT_RULES.md`.

## 2. Public and authentication

| Method | Path | Actor | Notes |
| --- | --- | --- | --- |
| POST | `/auth/register` | Public | Creates MEMBER only; throttled. |
| POST | `/auth/login` | Public | Returns Bearer token; throttled. |
| POST | `/auth/forgot-password` | Public | Enumeration-safe response. |
| POST | `/auth/reset-password` | Public | One-time hashed reset credential. |
| GET | `/auth/me` | Authenticated | Current account/roles allow-list. |
| POST | `/auth/logout` | Authenticated | Revokes current token. |
| POST | `/webhooks/payos` | payOS | Signature verified; idempotent by provider event/order. |

## 3. Admin

| Area | Methods and paths | Actor |
| --- | --- | --- |
| Dashboard | `GET /admin/dashboard` | ADMIN |
| Accounts | `GET /admin/accounts`, `GET /admin/accounts/{id}`, `PATCH /admin/accounts/{id}/status` | ADMIN |
| Roles | `PUT/DELETE /admin/accounts/{id}/roles/{MEMBER|PT|RECEPTIONIST|ADMIN}` | ADMIN |
| Packages | `GET/POST /admin/packages`, `GET/PATCH /admin/packages/{id}`, `PUT /admin/packages/{id}/benefits` | ADMIN |
| Equipment | `GET/POST /admin/equipment`, `PATCH /admin/equipment/{id}` | ADMIN |
| Muscle groups | `GET/POST /admin/muscle-groups`, `PATCH /admin/muscle-groups/{id}` | ADMIN |
| Exercises | `GET/POST /admin/exercises`, `GET/PATCH /admin/exercises/{id}` | ADMIN |
| Workout templates | `GET/POST /admin/workout-templates`, `GET/PATCH /admin/workout-templates/{id}` | ADMIN |
| Trainer profile | `GET /admin/accounts/{id}/trainer-profile` | ADMIN; same-branch read-only full profile |
| Trainer onboarding | `POST /admin/trainers`, `POST /admin/accounts/{id}/trainer-profile` | ADMIN; both require **Idempotency-Key** |

Catalog records are deactivated through state; there is no hard-delete API. Existing Membership/Plan/Workout snapshots are not rewritten.

### 3.1. Muscle Group catalog lifecycle

`GET /admin/muscle-groups` returns every catalog row, including `NGUNG_SU_DUNG`, ordered by `code` (then `id`). Each item is `{ id, code, name, description, status }`. `POST /admin/muscle-groups` accepts an optional `status`; the only values are `HOAT_DONG` and `NGUNG_SU_DUNG`, and omission defaults to `HOAT_DONG`. `PATCH /admin/muscle-groups/{id}` accepts any non-empty subset of `name`, `description`, and `status`; code, IDs, timestamps, branch/actor fields, and other authority fields are server-owned. Status input is trimmed and uppercased before validation.

There is deliberately no `DELETE /admin/muscle-groups/{id}`. Deactivation and reactivation use `PATCH` with `status: NGUNG_SU_DUNG` or `status: HOAT_DONG`. An identical normalized PATCH is a successful no-op: it does not change `updated_at`/`ngay_cap_nhat` and does not add an audit row. A real status transition writes one atomic `CAP_NHAT_TRANG_THAI_NHOM_CO` audit row with the actor, target `NHOM_CO`, correlation UUID, UTC timestamp, and before/after state. Missing rows return `404 MUSCLE_GROUP_NOT_FOUND`; invalid state or prohibited fields return `422`.

New Exercise-to-Muscle-Group relations may reference only `HOAT_DONG` groups. A stale or malicious request that creates a relation to an inactive group returns `422 INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`. Existing relations are historical/readable: deactivation never deletes or rewrites `bai_tap_nhom_co`, and Exercise list/detail DTOs include the related group `status`. When an Exercise replacement payload includes `muscle_groups`, every existing inactive relation must be included with its original role; adding, omitting/removing, or changing the role of an inactive relation returns the same `422` code atomically. Omitting `muscle_groups` means that dimension is untouched. Once reactivated, normal relation additions/removals/role changes are allowed. The service locks actor, Exercise (for updates), and referenced Muscle Group rows in deterministic ascending-ID order before validation and diff synchronization.

Trainer onboarding requires a UUID `Idempotency-Key`. The new-account endpoint creates the account, PT profile, role, and audit rows transactionally, then reports and stores `invitation: "QUEUED"` only after the existing password-reset dispatcher accepts the job. A queue acceptance failure returns `503`; the domain rows remain committed with stored `invitation: "NOT_QUEUED"`, and the same actor/key/body can recover the invitation without recreating or re-auditing those rows. Initial success is `201` with `replayed: false`; an ordinary replay or recovered invitation is `200` with `replayed: true`. The existing-account endpoint is `200`, returns `invitation: "NOT_REQUESTED"`, never sends a reset invitation, and hashes the account path plus only supplied canonical profile fields, preserving absent versus explicit `null`. A reused key with a different body or account path returns `409`.

Each successful account/profile/PT-role transition writes an append-only audit row with immutable safe snapshots. Account creation uses `before: null` and an after snapshot containing only `id`, `name`, `email`, `branch_id`, and `status`; it never records `mat_khau_bam`, tokens, or raw request data. Profile snapshots use exactly `id`, `account_id`, `trainer_code`, `introduction`, `specialties`, and `status`. Existing-profile updates compare normalized persisted values, keep omitted fields unchanged, represent explicit `null`, and write one profile audit only when a persisted value changes. PT-role snapshots use `assignment_id`, `account_id`, `role`, `granted_by_id`, `granted_at`, `revoked_at`, and `active`; regrant records revoked-before/active-after while preserving the assignment row ID and original `ngay_tao`. All audit rows from one logical onboarding transaction share one UUID `khoa_tuong_quan`. Mutation and audit writes share the domain transaction; audit failure rolls back the corresponding account/profile/role/idempotency changes. Active-role no-op and idempotent replay do not create duplicate successful audits. Invitation dispatch remains outside this domain transaction.

The Admin trainer-profile read returns exactly `{ account_id, trainer_profile_id, trainer_code, status, introduction, specialties, updated_at }` for an account and PT profile in the actor's branch. Missing account, foreign-branch account, and absent profile share `404 TRAINER_PROFILE_NOT_FOUND` with the normalized error envelope. The GET revalidates the active Admin role, performs no transaction lock, idempotency, audit, profile mutation, or timestamp update—including the current access-token telemetry row—and does not expose account contact fields, password/token data, role history, assignment, or audit data. Other protected endpoints retain normal valid-request access-token telemetry updates.

## 4. Profile, package, Membership and payment

| Method | Path | Actor |
| --- | --- | --- |
| GET/PATCH | `/profile` | Authenticated |
| GET/PATCH | `/profile/member` | MEMBER |
| GET/PUT | `/profile/member/availability` | MEMBER |
| GET/PUT | `/profile/member/equipment` | MEMBER |
| GET/PATCH | `/profile/trainer` | PT |
| GET | `/packages`, `/packages/{id}` | Authenticated |
| POST | `/packages/{id}/orders` | MEMBER, **Idempotency-Key** |
| GET | `/orders`, `/orders/{id}`, `/orders/{id}/payment` | MEMBER owner |
| GET | `/membership` | MEMBER |

Payment success grants ownership/snapshot only. The first valid paid entitlement use activates the current Membership period.

## 5. Gym

| Method | Path | Actor | Notes |
| --- | --- | --- | --- |
| POST | `/gym/qr` | MEMBER | Issues short-lived dynamic QR. |
| POST | `/gym/check-in` | RECEPTIONIST, ADMIN | Validates QR and check-in rules. |
| GET | `/gym/check-ins` | MEMBER | Owner history. |

## 6. PT assignment, direct service and proposal

| Method | Path | Actor |
| --- | --- | --- |
| GET | `/pt/assignments` | ADMIN; same-branch Member + PT resources |
| GET | `/pt/assignments/{id}` | ADMIN; same-branch Member + PT resources |
| POST | `/pt/assignments` | ADMIN |
| PATCH | `/pt/assignments/{id}/end` | ADMIN |
| POST | `/pt/assignments/{id}/reassign` | ADMIN |
| GET | `/pt/assignment` | MEMBER |
| GET | `/pt/members` | PT |
| GET | `/pt/members/{member}` | PT with current assignment |
| GET | `/pt/members/{member}/workout/plans/current` | PT with current assignment; official Plan read-only |
| GET | `/pt/members/{member}/workout/sessions` | PT with current assignment; immutable history read-only |
| GET | `/pt/members/{member}/workout/sessions/{session}` | PT with current assignment; immutable snapshot detail |
| GET | `/pt/direct-sessions` | MEMBER, PT; Member-owned history takes precedence for dual-role accounts |
| GET | `/pt/direct-sessions/history` | PT; authenticated trainer's own history across Members, including for dual-role accounts |
| POST | `/pt/direct-sessions/complete` | PT, **Idempotency-Key** |
| GET/POST | `/pt/members/{member}/proposals` | PT with current assignment; POST uses **Idempotency-Key** |
| GET/POST | `/pt/members/{member}/notes` | PT with current assignment |
| GET | `/pt/proposals`, `/pt/proposals/{id}`, `/pt/notes` | MEMBER owner |
| POST | `/pt/proposals/{id}/confirm` | MEMBER owner, **Idempotency-Key** |
| POST | `/pt/proposals/{id}/reject` | MEMBER owner, **Idempotency-Key** |

Admin assignment reads return `{data:{items,pagination}}` or one exact assignment DTO with only `id`, `member:{id,code,name}`, `trainer:{id,code,name,status}`, `start_at`, `end_at`, `reason`, server-UTC `is_current`, `created_at`, and `updated_at`. Both linked accounts must belong to the authenticated Admin's branch; foreign/missing rows are concealed as `404 ASSIGNMENT_NOT_FOUND`. List filters are the allow-listed `member_id`, `trainer_id`, `current`, `page`, and `per_page`, ordered by `start_at DESC, id DESC`. Assignment intervals use half-open `[start_at,end_at)` semantics, so adjacency is valid and overlap is rejected under actor/member/all-member-assignment/trainer locks. End is a server-time target-state no-op when already ended; reassign closes the old row at the new start and inserts a new row. Every successful create/end/reassign writes allow-listed before/after audit snapshots in the same transaction; no assignment mutation adds an `Idempotency-Key`, and timeout/5xx clients must reconcile through GET without blind retry.

### 6.1 PT member workspace read contracts

`GET /pt/direct-sessions` remains the shared Member/PT read. It returns `{data: DirectHistory[]}` for the authenticated Member's own ledger when the account has `MEMBER`; a dual-role account therefore continues to receive the Member-owned slice from this legacy path. A PT-only account retains the legacy PT-owned result on that path. PT Web history uses the dedicated `GET /pt/direct-sessions/history` route, which requires an active PT role and an existing trainer profile, then returns `{data: DirectHistory[]}` for that authenticated trainer's own confirmed rows across Members and past assignments. Each item contains only `history_id`, `assignment_id`, `member_id`, `trainer_id`, `term_id`, `usage_id`, `status`, `completed_at`, `confirmed_at`, and `notes`. Both paths order by confirmation timestamp descending and ID descending, return at most the latest 100 rows, and accept no actor ID selector. The dual-role PT must call the dedicated path for trainer history; neither path expands access to arbitrary Member resources. Missing PT profile on the PT history route returns `404 TRAINER_PROFILE_REQUIRED`.

All workspace routes below require `Authorization: Bearer <token>`, an active account with role `PT`, and an exact current assignment for the requested Member. The assignment interval is half-open `[ngay_bat_dau, ngay_ket_thuc)`: `start_at` is inclusive, `end_at` is exclusive, and `null` end is open. An unassigned, future, ended, old-PT, or foreign-PT request is concealed as `404` and does not fall back to Member-self or Admin routes. The backend checks the assignment at request time with server UTC and performs no Membership lookup, activation, quota consumption, audit write, or mutation.

`GET /pt/members/{member}` returns:

```json
{
  "data": {
    "member": {
      "id": 42,
      "code": "HV_001",
      "name": "Member",
      "training_goal": "TANG_SUC_MANH",
      "training_experience": "MOI_BAT_DAU",
      "desired_training_days": 3,
      "session_duration_minutes": 60,
      "profile_version": 1,
      "updated_at": "2026-08-30T02:00:00.000000Z"
    },
    "assignment": {
      "id": 7,
      "start_at": "2026-08-29T02:00:00.000000Z",
      "end_at": null
    }
  }
}
```

The `member` and `assignment` objects are allow-listed exactly as shown. Email, phone, birth date, credentials, branch authority, end reason, audit actor, and other internal fields are not returned.

`GET /pt/members/{member}/workout/plans/current` returns `{ plan, future_schedule, schedule_window }`. `plan` is the existing safe official current Plan DTO or `null`; a pending/unconfirmed Proposal is never presented as the official Plan. `future_schedule` reuses the existing safe scheduled-workout item DTO. `schedule_window` is server-authoritative and contains `from`, `to` (inclusive dates covering at most 92 calendar days from the current business date), and `timezone: "Asia/Ho_Chi_Minh"`.

`GET /pt/members/{member}/workout/sessions?before_id&limit` reuses the existing `ListWorkoutSessionsRequest` validation (`limit` 1..100, default 20; positive integer `before_id`) and existing immutable session DTO. Results are ordered by descending session ID; `before_id` is an exclusive stable cursor. Invalid query values return `422`.

`GET /pt/members/{member}/workout/sessions/{session}` returns the existing immutable session snapshot only when the session belongs to the authorized Member. A session belonging to another Member is concealed as `404`, including when the same PT is currently assigned to that other Member. There are no Plan/Session write routes in this workspace contract.

Only PT-confirmed direct-session completion consumes direct PT quota. PT Proposal confirmation creates a new immutable Workout Plan Version.

## 7. PT Realtime Chat

| Method | Path | Actor |
| --- | --- | --- |
| GET | `/pt/chat/conversations` | MEMBER, current PT scope |
| POST | `/pt/chat/conversations/current` | MEMBER, PT |
| GET | `/pt/chat/conversations/{id}` | Participant scope |
| GET | `/pt/chat/conversations/{id}/messages` | Participant scope, sequence cursor |
| POST | `/pt/chat/conversations/{id}/messages` | Current participant; body has stable `client_message_id` |
| GET/POST | `/broadcasting/auth` | MEMBER, PT; private channel auth |

Private channel: `private-pt.conversation.{id}`; event alias: `pt.chat.message.sent`. Q05: Member keeps historical read; old/new PT cannot read or subscribe to an old assignment conversation. Database history is source of truth. Scheduler retries due outbox events; clients dedupe by `message_id`.

## 8. Gemini AI Proposal and Apply

| Method | Path | Actor | Notes |
| --- | --- | --- | --- |
| POST | `/assistant/requests` | MEMBER | **Idempotency-Key**, one accepted business request = one AI quota. |
| GET | `/assistant/requests` | MEMBER owner | History. |
| GET | `/assistant/requests/{id}` | MEMBER owner | Safe request/proposal status. |
| GET | `/assistant/proposals/{id}` | MEMBER owner | Immutable preview + expiry state. |
| POST | `/assistant/proposals/{id}/apply` | MEMBER owner | **Idempotency-Key**; no second Gemini call/quota/activation. |

Gemini receives only bounded Backend candidates and returns structured JSON. Output is untrusted and revalidated for candidate ownership, equipment AND semantics, ranges, profile/availability and stale Plan Version before persistence/Apply.

## 9. Workout and Progress

| Area | Methods and paths | Actor |
| --- | --- | --- |
| Templates/Plans | `GET /workout/templates[/{id}]`, `GET /workout/plans`, `GET /workout/plans/current`, `GET /workout/plans/{id}` | MEMBER |
| Schedule | `GET /workout/schedule`, `POST /workout/scheduled-sessions/{id}/start`, `POST /workout/scheduled-sessions/{id}/skip` | MEMBER; start uses **Idempotency-Key** |
| Session | `GET /workout/sessions[/{id}]`, `POST /workout/sessions/{session}/exercises/{exercise}/sets`, `POST /workout/sessions/{id}/complete` | MEMBER; mutations use **Idempotency-Key** |
| Body | `POST /progress/body`, `GET /progress/body`, `GET /progress/body/latest` | MEMBER; create idempotent by `entry_id` |
| Overview | `GET /progress/overview`, `GET /progress/exercises/{exercise}` | MEMBER |
| PT progress read | `GET /pt/members/{member}/progress/overview`, `/body`, `/exercises/{exercise}` | Current assigned PT |

Workout Session completed history is immutable. MVP does not support Free Workout outside a scheduled session.

## 10. Internal operations

- `php artisan pt-chat:retry-outbox {--limit=N}` retries due Chat outbox rows. Scheduler runs it every minute with overlap protection.
- `php artisan reverb:start` serves private Chat broadcasts.
- `/up` is the framework health endpoint.
- Gemini/payOS/Reverb credentials are Backend-only and never belong in Web/Mobile environment files.

The executable source of truth for exact routes/middleware is `BE/routes/api.php`; business semantics remain governed by `PROJECT_RULES.md`.
