import type { AuthSuccess, LoginResult, Role, SchoolIdentity, User } from './types';

export const API_URL = (process.env.EXPO_PUBLIC_API_URL ?? 'https://edlink.space/api/v1').replace(/\/$/, '');
type Envelope<T> = { data: T; meta?: Record<string, unknown> };

export class ApiError extends Error {
  constructor(message: string, public readonly status: number, public readonly errors?: Record<string, string[]>) { super(message); }
}

async function request<T>(path: string, token?: string, init: RequestInit = {}): Promise<Envelope<T>> {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20_000);
  let response: Response;
  try {
    response = await fetch(`${API_URL}${path}`, { ...init, signal: controller.signal, headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}), ...init.headers } });
  } catch (error) {
    if (error instanceof Error && error.name === 'AbortError') throw new ApiError('The Edlink server took too long to respond. Please try again.', 408);
    throw new ApiError('Cannot connect to Edlink. Check your internet connection and try again.', 0);
  } finally {
    clearTimeout(timeout);
  }
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    const validationMessage = payload.errors ? Object.values(payload.errors as Record<string, string[]>).flat()[0] : undefined;
    throw new ApiError(validationMessage ?? payload.message ?? 'Something went wrong.', response.status, payload.errors);
  }
  return payload as Envelope<T>;
}

export const api = {
  school: (school_number: string) => request<SchoolIdentity>('/auth/school', undefined, { method: 'POST', body: JSON.stringify({ school_number }) }),
  login: (body: { school_number: string; email: string; password: string; device_name: string; expected_role: Role }) => request<LoginResult>('/auth/login', undefined, { method: 'POST', body: JSON.stringify(body) }),
  verifyOtp: (body: { challenge_token: string; code: string; device_name: string }) => request<AuthSuccess>('/auth/otp/verify', undefined, { method: 'POST', body: JSON.stringify(body) }),
  resendOtp: (challenge_token: string) => request<{ sent: boolean; masked_email: string }>('/auth/otp/resend', undefined, { method: 'POST', body: JSON.stringify({ challenge_token }) }),
  forgotPassword: (school_number: string, email: string) => request<{ message: string }>('/auth/password/forgot', undefined, { method: 'POST', body: JSON.stringify({ school_number, email }) }),
  resetPassword: (body: { token: string; school_number: string; email: string; password: string; password_confirmation: string }) => request<{ reset: boolean }>('/auth/password/reset', undefined, { method: 'POST', body: JSON.stringify(body) }),
  me: (token: string) => request<User>('/auth/me', token),
  logout: (token: string) => request<{ logged_out: boolean }>('/auth/logout', token, { method: 'POST' }),
  get: <T>(path: string, token: string) => request<T>(path, token),
  post: <T>(path: string, token: string, body: unknown) => request<T>(path, token, { method: 'POST', body: JSON.stringify(body) }),
};
