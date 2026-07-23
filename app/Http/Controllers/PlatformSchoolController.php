<?php

namespace App\Http\Controllers;

use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Support\SubscriptionPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

    public function licences(): View
    {
        $schools = School::withCount(['students as active_students_count' => fn ($query) => $query->where('status', 'active')])->orderBy('name')->get();
        return view('platform.licences.index', ['schools' => $schools, 'plans' => SubscriptionPlans::PLANS]);
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
        $school->update($data);
        PlatformAuditLog::create(['platform_admin_id' => Auth::guard('platform')->id(), 'event' => 'platform.licence.updated', 'metadata' => ['school_id' => $school->id, 'school' => $school->name, 'plan' => $data['license_plan'], 'status' => $data['license_status']], 'ip_address' => $request->ip(), 'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString() ?: null]);
        return back()->with('status', $school->name.' licence was updated.');
    }
}