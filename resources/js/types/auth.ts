export interface User {
  id: number
  username: string
  email: string
  first_name: string | null
  last_name: string | null
  phone_number: string | null
  role: string
  auth_provider: string | null
  email_verified: boolean
  last_login_at: string | null
  avatar_optimized_url: string | null
  avatar_thumbnail_url: string | null
  created_at: string
  updated_at: string
}

export interface UpdateUserRequest {
  username?: string
  email?: string
  firstName?: string
  lastName?: string
  phoneNumber?: string
}

export interface UpdateProfilePayload extends UpdateUserRequest {
  currentPassword?: string
  newPassword?: string
  confirmNewPassword?: string
  avatar?: File
}

export interface CreateUserRequest {
  username: string
  email: string
  firstName: string
  lastName: string
  phoneNumber: string
  password: string
}
