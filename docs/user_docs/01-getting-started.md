# 1. Registration and Login

## 1.1 Registration (`/register`)

The "Registration" page is available only to unauthenticated users. There are two ways to create an account.

### Via email and password

Form fields:

| Field | Required | Rules |
|---|---|---|
| First name | yes | up to 255 characters |
| Last name | yes | up to 255 characters |
| Username (login) | yes | unique, up to 255 characters |
| Email | yes | valid address, unique in the system |
| Phone | no | entered after the `+380` prefix, 9 digits only |
| Password | yes | minimum 8 characters |
| Password confirmation | yes | must match the password |

If the username or email is already taken or a required field is empty, an error message appears under the corresponding field, and a banner "Please correct the errors below." appears above the form (the messages under the fields are currently in English: "The first name field is required.", "This username is already taken.", etc.).

The password fields have an eye-icon button **"Show password"** to reveal the entered value.

After successful registration, the user is **logged in immediately** and lands on the Dashboard. Email confirmation is not required.

### Via Google or GitHub

At the top of the registration form there are **"Via Google"** and **"Via GitHub"** buttons:

- If an account with that email already exists — the social account is linked to it, and you are logged into the existing account.
- If no account exists — one is created automatically: first and last name are taken from the social profile, the username is generated from the name (a suffix `_2`, `_3`... is added on collision), and the email is considered confirmed.
- In case of an error on the provider's side, you'll be returned to the login page with the message "Authorization error via provider. Please try again."

## 1.2 Login (`/login`)

Fields: **Email**, **Password**, checkbox **"Remember me"** (extended session).

Behavior rules:

- Incorrect email or password → "Invalid credentials." (without specifying which one is wrong).
- If the account was created via Google/GitHub and no password was set → the system will suggest: "Please use google/github to login." Use the corresponding social login button.
- After logging in, you land on the Dashboard (or the page you were trying to open before logging in).

The "Login / Registration" tabs at the top of the form let you switch between the pages. Below the form are the Google and GitHub login buttons.

## 1.3 Password Recovery

> **Note:** the login page has no "Forgot password?" link — the recovery page is only accessible via the direct URL `/forgot-password`.

Process:

1. On the `/forgot-password` page, enter your email and click **"Send link"**.
2. If the email exists in the system — a green banner appears: "A password reset link has been sent to your email." If not — "Could not find a user with that email address."
3. Follow the link from the email — the "Password Reset" page opens with the email already filled in (non-editable).
4. Enter a new password (minimum 8 characters) and confirm it, then click **"Reset password"**.
5. On success, you'll be redirected to the login page. **Note:** the login page does not show a confirmation — simply log in with your new password.
6. If the link is expired or invalid — "Could not reset password. The link is invalid or expired." Start the process again.

## 1.4 Logging Out

The **"Log out"** button is located in the user dropdown menu (avatar in the top-right corner) and in the bottom part of the mobile menu. After logging out, you return to the login page.
