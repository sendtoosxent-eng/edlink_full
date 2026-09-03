# Edlink mobile

React Native (Expo) client for the existing Edlink Laravel API. The first vertical slice supports teacher, student, and parent authentication; role-aware dashboards; parent child switching; attendance viewing; teacher attendance entry; and published results.

## Run on the Pixel emulator

From the repository root, start Laravel so Android can reach it:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

In another terminal:

```bash
cd mobile
npm run android
```

The app connects to `https://edlink.space/api/v1` by default. Override it for local development by copying `.env.example` to `.env` and changing `EXPO_PUBLIC_API_URL` (for example, Android Emulator uses `http://10.0.2.2:8000/api/v1` for a server running on the host Mac).

Mobile sign-in requires an active school, a verified teacher/student/parent account, its school number, and its normal Edlink password.
