import type { AccountPreview, AuthSuccess, LoginResult, Role, SchoolIdentity, User } from './types';

export const API_URL = (process.env.EXPO_PUBLIC_API_URL ?? 'https://edlink.space/api/v1').replace(/\/$/, '');
type Envelope<T> = { data: T; meta?: Record<string, unknown> };
type ErrorPayload = { message?: unknown; error?: unknown; errors?: unknown };

export class ApiError extends Error {
  constructor(message: string, public readonly status: number, public readonly errors?: Record<string, string[]>) { super(message); }
}

function apiErrorMessage(payload: ErrorPayload, status: number, authenticated: boolean) {
  const errors = payload.errors && typeof payload.errors === 'object' && !Array.isArray(payload.errors)
    ? payload.errors as Record<string, unknown>
    : undefined;
  const validationMessage = errors
    ? Object.values(errors).flatMap(value => Array.isArray(value) ? value : [value]).find(value => typeof value === 'string')
    : undefined;
  if (typeof validationMessage === 'string' && validationMessage.trim()) return validationMessage;

  const serverMessage = [payload.message, payload.error].find(value => typeof value === 'string' && value.trim()) as string | undefined;
  if (status === 401 && (!serverMessage || /^unauthenticated\.?$/i.test(serverMessage.trim()))) {
    return authenticated
      ? 'Your session has expired or is no longer valid. Please sign in again.'
      : 'We could not verify your sign-in details. Check them and try again.';
  }
  if (serverMessage) return serverMessage;
  if (status === 403) return 'You do not have permission to perform this action.';
  if (status >= 500) return 'The Edlink server could not complete this request. Please try again.';
  return `The request could not be completed (error ${status}). Please try again.`;
}

async function request<T>(path: string, token?: string, init: RequestInit = {}): Promise<Envelope<T>> {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20_000);
  let response: Response;
  try {
    response = await fetch(`${API_URL}${path}`, { ...init, signal: controller.signal, headers: { Accept: 'application/json', ...(init.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }), ...(token ? { Authorization: `Bearer ${token}` } : {}), ...init.headers } });
  } catch (error) {
    if (error instanceof Error && error.name === 'AbortError') throw new ApiError('The Edlink server took too long to respond. Please try again.', 408);
    throw new ApiError('Cannot connect to Edlink. Check your internet connection and try again.', 0);
  } finally {
    clearTimeout(timeout);
  }
  if (response.status === 403 && !response.headers.get('content-type')?.toLowerCase().includes('json')) {
    throw new ApiError('Edlink’s hosting server is blocking access (403). Please contact the Edlink administrator to restore server access.', 403);
  }
  const payload: ErrorPayload = await response.json().catch(() => ({}));
  if (!response.ok) {
    const errors = payload.errors && typeof payload.errors === 'object' && !Array.isArray(payload.errors)
      ? payload.errors as Record<string, string[]>
      : undefined;
    throw new ApiError(apiErrorMessage(payload, response.status, Boolean(token)), response.status, errors);
  }
  return payload as Envelope<T>;
}

export const api = {
  account: (school_number: string, email: string, expected_role: Role) => request<AccountPreview | { accepted: true }>('/auth/account', undefined, { method: 'POST', body: JSON.stringify({ school_number, email, expected_role }) }),
  school: (school_number: string) => request<SchoolIdentity>('/auth/school', undefined, { method: 'POST', body: JSON.stringify({ school_number }) }),
  login: (body: { school_number: string; email: string; password: string; device_name: string; expected_role: Role }) => request<LoginResult>('/auth/login', undefined, { method: 'POST', body: JSON.stringify(body) }),
  verifyOtp: (body: { challenge_token: string; code: string; device_name: string }) => request<AuthSuccess>('/auth/otp/verify', undefined, { method: 'POST', body: JSON.stringify(body) }),
  resendOtp: (challenge_token: string) => request<{ sent: boolean; masked_email: string }>('/auth/otp/resend', undefined, { method: 'POST', body: JSON.stringify({ challenge_token }) }),
  forgotPassword: (school_number: string, email: string) => request<{ message: string }>('/auth/password/forgot', undefined, { method: 'POST', body: JSON.stringify({ school_number, email }) }),
  resetPassword: (body: { token: string; school_number: string; email: string; password: string; password_confirmation: string }) => request<{ reset: boolean }>('/auth/password/reset', undefined, { method: 'POST', body: JSON.stringify(body) }),
  me: (token: string) => request<User>('/auth/me', token),
  logout: (token: string) => request<{ logged_out: boolean }>('/auth/logout', token, { method: 'POST' }),
  get: <T>(path: string, token: string) => request<T>(path, token),
  upload: <T>(path: string, token: string, body: FormData) => request<T>(path, token, { method: 'POST', body }),
  put: <T>(path: string, token: string, body: unknown) => request<T>(path, token, { method: 'PUT', body: JSON.stringify(body) }),
  post: <T>(path: string, token: string, body: unknown) => request<T>(path, token, { method: 'POST', body: JSON.stringify(body) }),
};
