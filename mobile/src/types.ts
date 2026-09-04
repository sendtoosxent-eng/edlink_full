export type Role = 'teacher' | 'student' | 'parent';
export type SchoolIdentity = { number: string; name: string; logo_url?: string | null; is_demo: boolean };
export type User = { id: number; name: string; email: string; role: Role; avatar_url?: string | null; school: { id: number; number: string; name: string }; permissions: string[] };
export type AuthSuccess = { otp_required: false; token: string; user: User };
export type OtpChallenge = { otp_required: true; challenge_token: string; masked_email: string; expires_in: number };
export type LoginResult = AuthSuccess | OtpChallenge;
export type Student = { id: number; name: string; admission_no: string; photo_url?: string | null; class?: string | null; stream?: string | null };
export type Assignment = { school_class_id: number; class_name: string; subject_id: number | null; subject_name: string; attendance_type: 'daily' | 'subject' };
export type AttendanceStatus = 'present' | 'absent' | 'late' | 'excused';
export type AttendanceRow = { student: Pick<Student, 'id' | 'name' | 'admission_no'>; status: AttendanceStatus };
export type AttendanceRecord = { id: number; attendance_date: string; status: AttendanceStatus; session_key: string };
export type Dashboard = {
  date: string; student: Student | null;
  today_timetable: Array<{ id: number; subject?: string | null; label?: string | null; starts_at: string; ends_at: string; class_name?: string | null }>;
  next_lesson?: { id: number; subject?: string | null; label?: string | null; starts_at: string; ends_at: string; class_name?: string | null } | null;
  homework: Array<{ id: number; title: string; due_at?: string; subject?: { name: string } }>;
  attendance: Record<string, number>;
  events: Array<{ id: number; title?: string; name?: string; event_date: string }>;
  analytics?: {
    attendance_labels: string[];
    present_series: number[];
    absent_series: number[];
    performance_labels: string[];
    performance_series: number[];
    stats: Record<string, number>;
  };
};
export type ExamResult = { id: number; name: string; term?: string | null; published_at?: string | null; papers: Array<{ id: number; subject?: string | null; score?: number | null; maximum_score: number }> };
