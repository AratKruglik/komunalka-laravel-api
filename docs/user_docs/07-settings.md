# 7. Account Settings

The "Settings" page (`/settings`) has four tabs: **Profile**, **Security**, **Appearance**, **Account**.

## 7.1 Profile

Editable data:

| Field | Notes |
|---|---|
| Avatar | JPG, PNG, GIF, or HEIC up to 2 MB; briefly processed after upload (the indicator disappears automatically) |
| First name | up to 255 characters |
| Last name | up to 255 characters |
| Phone | up to 50 characters |

**Email cannot be changed** — the field is shown but locked ("Email cannot be changed").

**"Save changes"** button → message "Profile updated successfully."

## 7.2 Security

### Change Password

Fields: current password, new password, confirm new password. Below the new password there is a **strength indicator** with criteria: minimum 8 characters, at least 1 uppercase letter, at least 1 digit. If the current password is incorrect — "Current password is incorrect." On success — "Password changed successfully.", the form is cleared.

> If the account was created via Google/GitHub and no password has been set, set a password first — without one it is impossible to disconnect a social account or delete the account.

### Connected Accounts

A list of the **Google** and **GitHub** providers with "Connected" / "Disconnected" badges:

- **Connect** — redirects to the respective service's authorization page; after confirmation the social account is linked to the account and can be used to sign in.
- **Disconnect** — opens a "Disconnect {provider}?" dialog with a **password** field for confirmation. After disconnecting, sign-in is possible only via email and password. If no password has been set, the system will not allow disconnecting.

## 7.3 Appearance

- **Theme**: Light / Dark / System. The choice is stored in a browser cookie and applied instantly.
- **Language**: currently only Ukrainian is available.

## 7.4 Account

- **Export data** — the "Export data (CSV)" button is present, but is not yet functional in the current version (see [known limitations](10-known-limitations.md)).
- **Danger zone → Delete account**: a "Delete account?" dialog with confirmation. Deletion **irreversibly destroys all data** — addresses, meters, readings. After deletion you will be logged out to the login page (no confirmation is shown there).

The confirmation method depends on how you sign in:

| Your account | What you enter for confirmation |
|---|---|
| With a password | **Password** — the same one you use to sign in |
| Via Google/GitHub, no password set | **Your email or username** — exactly as in your profile |
