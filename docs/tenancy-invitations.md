# Tenant Invitation Onboarding

## Goal

Provide a clear API contract for tenant invitations so the panel can support:

- invitation preview by token
- invited-user registration
- existing-user acceptance after sign-in

## Public endpoints

### Preview invitation

`GET /api/v1/tenants/invitations/{token}`

Purpose:

- inspect invitation state without authentication
- determine whether the frontend should guide the user to register or log in

Response shape:

```json
{
  "data": {
    "email": "invitee@example.com",
    "role": "support",
    "permissions": ["view users"],
    "status": "pending",
    "is_pending": true,
    "has_existing_account": false,
    "recommended_action": "register",
    "tenant": {
      "id": 1,
      "name": "Operations Workspace",
      "slug": "operations-workspace"
    },
    "links": {
      "accept": "https://panel.example.com/invitations/<token>",
      "register": "https://panel.example.com/register?invitation=<token>",
      "login": "https://panel.example.com/login?invitation=<token>",
      "api": {
        "show": "https://api.example.com/api/v1/tenants/invitations/<token>",
        "register": "https://api.example.com/api/v1/tenants/invitations/<token>/register",
        "accept": "https://api.example.com/api/v1/tenants/invitations/<token>/accept"
      }
    },
    "expires_at": "2026-04-30T10:00:00Z",
    "accepted_at": null,
    "revoked_at": null,
    "created_at": "2026-04-23T10:00:00Z"
  },
  "meta": {}
}
```

### Register from invitation

`POST /api/v1/tenants/invitations/{token}/register`

Request body:

```json
{
  "name": "Invited User",
  "password": "password123"
}
```

Rules:

- the invited email comes from the invitation, not from the client payload
- the invitation must still be pending
- if an account already exists for the invited email, the API returns validation errors and the frontend should redirect to login

Success result:

- creates the user
- accepts the invitation
- switches the user into the invited tenant
- returns auth token and tenant context immediately

### Accept invitation as an existing authenticated user

`POST /api/v1/tenants/invitations/{token}/accept`

Rules:

- requires authentication
- authenticated user email must match the invitation email
- invitation must still be pending

## Invitation states

Supported `status` values:

- `pending`
- `accepted`
- `revoked`
- `expired`

Frontend behavior should be based on `status` first and `recommended_action` second.

## Frontend handoff rules

When `status` is `pending`:

- if `recommended_action` is `register`, send the user through the invitation registration flow
- if `recommended_action` is `login`, send the user through sign-in and then call the authenticated accept endpoint

When `status` is not `pending`:

- do not offer accept/register actions
- render a terminal state based on the invitation status

## Configuration

The invitation preview resource exposes frontend links built from:

- `TENANCY_INVITATION_ACCEPT_URL`
- `TENANCY_INVITATION_REGISTER_URL`
- `TENANCY_INVITATION_LOGIN_URL`

These map to:

- `tenancy.invitations.accept_url`
- `tenancy.invitations.register_url`
- `tenancy.invitations.login_url`

Use `{token}` in configured URLs when the frontend route expects path substitution.
