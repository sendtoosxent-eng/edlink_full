<?php

namespace App\Http\Controllers;

use App\Models\DemoRegistration;
use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Models\User;
use App\Support\SubscriptionPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlatformSchoolController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $schools = School::query()
            ->withCount(['students as active_students_count' => fn ($query) => $query->where('status', 'active'), 'users'])
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('school_number', 'like', "%{$search}%")))
            ->when($request->filled('plan'), fn ($query) => $query->where('license_plan', $request->query('plan')))
            ->when($request->filled('status'), fn ($query) => $query->where('license_status', $request->query('status')))
            ->latest()->paginate(20)->withQueryString();

        return view('platform.schools.index', compact('schools', 'search'));
    }

    public function create(): View
    {
        return view('platform.schools.create', ['plans' => SubscriptionPlans::PLANS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', Rule::in(['kindergarten', 'primary', 'secondary', 'combined', 'tertiary'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'license_plan' => ['required', Rule::in(array_keys(SubscriptionPlans::PLANS))],
            'license_status' => ['required', Rule::in(['active', 'trial', 'suspended'])],
            'license_started_at' => ['required', 'date'],
            'license_expires_at' => ['nullable', 'required_if:license_status,trial', 'date', 'after_or_equal:license_started_at'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $schoolData = collect($validated)->only([
            'name', 'school_type', 'email', 'phone', 'address', 'license_plan',
            'license_status', 'license_started_at', 'license_expires_at',
        ])->all();
        $schoolData['slug'] = Str::slug($schoolData['name']).'-'.Str::lower(Str::random(5));
        $schoolData['status'] = match ($schoolData['license_status']) {
            'trial' => 'demo',
            'suspended' => 'inactive',
            default => 'active',
        };
        $schoolData['is_demo'] = $schoolData['license_status'] === 'trial';
        $schoolData['demo_expires_at'] = $schoolData['is_demo'] ? $schoolData['license_expires_at'] : null;
        $schoolData['license_student_limit'] = SubscriptionPlans::limit($schoolData['license_plan']);

        $school = DB::transaction(function () use ($schoolData, $validated, $request): School {
            $school = School::create($schoolData);
            $administrator = User::create([
                'school_id' => $school->id,
                'name' => $validated['admin_name'],
                'email' => Str::lower($validated['admin_email']),
                'password' => $validated['admin_password'],
                'role' => 'admin',
            ]);
            $administrator->forceFill(['email_verified_at' => now()])->save();

            PlatformAuditLog::create([
                'platform_admin_id' => Auth::guard('platform')->id(),
                'event' => 'platform.school.created',
                'metadata' => [
                    'school_id' => $school->id,
                    'school' => $school->name,
                    'plan' => $school->license_plan,
                    'status' => $school->license_status,
                    'administrator_id' => $administrator->id,
                    'administrator_email' => $administrator->email,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString() ?: null,
            ]);

            return $school;
        });

        return redirect()->route('platform.schools.show', $school)->with(
            'status',
            $school->name.' was created. Login with school number '.$school->school_number.' and the administrator credentials you supplied.'
        );
    }

    public function licences(): View
    {
        $schools = School::withCount(['students as active_students_count' => fn ($query) => $query->where('status', 'active')])->orderBy('name')->get();

        return view('platform.licences.index', ['schools' => $schools, 'plans' => SubscriptionPlans::PLANS]);
    }

    public function show(School $school): View
    {
        $school->loadCount([
            'students',
            'students as active_students_count' => fn ($query) => $query->where('status', 'active'),
            'users',
            'classes',
            'streams',
            'terms',
        ])->load([
            'terms' => fn ($query) => $query->latest()->limit(5),
            'users' => fn ($query) => $query->orderBy('name')->limit(8),
        ]);

        return view('platform.schools.show', [
            'school' => $school,
            'smsConfiguration' => $school->smsConfiguration()->firstOrNew(['provider' => 'africastalking', 'enabled' => false]),
            'canDelete' => $school->is_demo || in_array($school->license_status, ['suspended', 'expired'], true),
        ]);
    }

    public function edit(School $school): View
    {
        return view('platform.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', Rule::in(['kindergarten', 'primary', 'secondary', 'combined', 'tertiary'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'motto' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_demo' => ['nullable', 'boolean'],
            'demo_expires_at' => ['nullable', 'date'],
        ]);
        $data['is_demo'] = $request->boolean('is_demo');
        $before = $school->only(array_keys($data));
        $school->update($data);

        PlatformAuditLog::create([
            'platform_admin_id' => Auth::guard('platform')->id(),
            'event' => 'platform.school.updated',
            'metadata' => [
                'school_id' => $school->id,
                'school' => $school->name,
                'before' => $before,
                'changed' => $school->getChanges(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString() ?: null,
        ]);

        return redirect()->route('platform.schools.show', $school)->with('status', $school->name.' details were updated.');
    }

    public function destroy(Request $request, School $school): RedirectResponse
    {
        if (! $school->is_demo && ! in_array($school->license_status, ['suspended', 'expired'], true)) {
            throw ValidationException::withMessages([
                'school_number' => 'Only demo, suspended, or expired schools can be permanently removed.',
            ]);
        }

        $data = $request->validate([
            'school_number' => ['required', Rule::in([$school->school_number])],
        ], [
            'school_number.in' => 'The school number does not match. No data was removed.',
        ]);

        $snapshot = [
            'school_id' => $school->id,
            'school' => $school->name,
            'school_number' => $data['school_number'],
            'is_demo' => $school->is_demo,
            'license_status' => $school->license_status,
            'students' => $school->students()->count(),
            'users' => $school->users()->count(),
        ];

        DB::transaction(function () use ($school, $request, $snapshot): void {
            $tenantEmails = $school->users()->whereNotNull('email')->pluck('email');

            // Releasing these claims lets a deliberately removed demo register again.
            if ($tenantEmails->isNotEmpty()) {
                DemoRegistration::whereIn('email', $tenantEmails)->delete();
            }

            // Users are nullable on school deletion, so remove tenant accounts explicitly.
            // A deliberate platform-level demo purge may remove accounting evidence; normal
            // operational deletion remains restricted so posted evidence cannot disappear.
            DB::table('fixed_asset_depreciations')->where('school_id', $school->id)->delete();
            DB::table('fixed_assets')->where('school_id', $school->id)->delete();
            DB::table('fixed_asset_categories')->where('school_id', $school->id)->delete();
            DB::table('accounting_budget_lines')->whereIn('accounting_budget_id', DB::table('accounting_budgets')->where('school_id', $school->id)->select('id'))->delete();
            DB::table('accounting_budgets')->where('school_id', $school->id)->delete();
            DB::table('student_fee_assessments')->where('school_id', $school->id)->delete();
            DB::table('accounting_journal_lines')->where('school_id', $school->id)->delete();
            DB::table('accounting_journals')->where('school_id', $school->id)->whereNotNull('reversal_of_id')->delete();
            DB::table('accounting_journals')->where('school_id', $school->id)->delete();
            DB::table('account_mappings')->where('school_id', $school->id)->delete();
            DB::table('financial_accounts')->where('school_id', $school->id)->update(['ledger_account_id' => null]);
            while (DB::table('ledger_accounts')->where('school_id', $school->id)->exists()) {
                DB::table('ledger_accounts')->where('school_id', $school->id)->whereNotIn('id', DB::table('ledger_accounts')->where('school_id', $school->id)->whereNotNull('parent_id')->select('parent_id'))->delete();
            }
            DB::table('cost_centres')->where('school_id', $school->id)->delete();
            DB::table('accounting_funds')->where('school_id', $school->id)->delete();
            DB::table('accounting_suppliers')->where('school_id', $school->id)->delete();
            DB::table('accounting_periods')->where('school_id', $school->id)->delete();
            DB::table('fiscal_years')->where('school_id', $school->id)->delete();
            $school->users()->delete();
            $school->delete();

            PlatformAuditLog::create([
                'platform_admin_id' => Auth::guard('platform')->id(),
                'event' => 'platform.school.deleted',
                'metadata' => $snapshot,
                'ip_address' => $request->ip(),
                'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString() ?: null,
            ]);
        });

        return redirect()->route('platform.schools')->with('status', $snapshot['school'].' and its tenant data were permanently removed.');
    }

    public function updateLicence(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'license_plan' => ['required', Rule::in(array_keys(SubscriptionPlans::PLANS))],
            'license_status' => ['required', Rule::in(['active', 'trial', 'suspended', 'expired'])],
            'license_started_at' => ['nullable', 'date'],
            'license_expires_at' => ['nullable', 'date', 'after_or_equal:license_started_at'],
        ]);
        $data['license_student_limit'] = SubscriptionPlans::limit($data['license_plan']);

        // Activating a paid licence must also retire any expired demo state.
        // Otherwise isExpiredDemo() continues to block the school's users.
        if ($data['license_status'] === 'active') {
            $data['status'] = 'active';
            $data['is_demo'] = false;
            $data['demo_expires_at'] = null;
        }

        DB::transaction(function () use ($data, $request, $school): void {
            $before = $school->only(array_keys($data));
            $school->update($data);

            PlatformAuditLog::create([
                'platform_admin_id' => Auth::guard('platform')->id(),
                'event' => 'platform.licence.updated',
                'metadata' => [
                    'school_id' => $school->id,
                    'school' => $school->name,
                    'plan' => $data['license_plan'],
                    'status' => $data['license_status'],
                    'before' => $before,
                    'changed' => $school->getChanges(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString() ?: null,
            ]);
        });

        return back()->with('status', $school->name.' licence was updated.');
    }
}
