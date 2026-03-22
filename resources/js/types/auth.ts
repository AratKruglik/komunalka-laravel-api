export interface User {
  id: number
  username: string
  email: string
  firstName: string | null
  lastName: string | null
  phoneNumber: string | null
  role: string
  authProvider: string | null
  emailVerified: boolean
  lastLoginAt: string | null
  avatarOptimizedUrl: string | null
  avatarThumbnailUrl: string | null
  createdAt: string
  updatedAt: string
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
