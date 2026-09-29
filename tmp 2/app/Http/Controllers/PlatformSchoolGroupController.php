<?php

namespace App\Http\Controllers;

use App\Models\{Designation, PlatformAuditLog, School, SchoolGroup, User};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformSchoolGroupController extends Controller
{
    public function index(): View
    {
        return view('platform.groups.index', [
            'groups' => SchoolGroup::withCount('schools')->with('schools:id,school_group_id,name')->orderBy('name')->get(),
            'availableSchools' => School::whereNull('school_group_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:school_groups,code'],
            'school_ids' => ['required', 'array', 'min:2'],
            'school_ids.*' => ['integer', Rule::exists('schools', 'id')->whereNull('school_group_id')],
        ]);

        $group = DB::transaction(function () use ($data, $request): SchoolGroup {
            $group = SchoolGroup::create(['name' => $data['name'], 'code' => strtoupper($data['code'])]);
            School::whereIn('id', $data['school_ids'])->update(['school_group_id' => $group->id]);
            $this->audit($request, 'platform.school_group.created', ['group_id' => $group->id, 'school_ids' => $data['school_ids']]);
            return $group;
        });

        return redirect()->route('platform.groups.show', $group)->with('status', 'School group created. Grant its director access below.');
    }

    public function show(SchoolGroup $schoolGroup): View
    {
        $schoolGroup->load(['schools' => fn ($query) => $query->withCount('students')->orderBy('name')]);
        $schoolIds = $schoolGroup->schools->pluck('id');
        $memberIds = DB::table('school_user_access')->whereIn('school_id', $schoolIds)->where('can_view_group', true)->pluck('user_id')->unique();
        $assignments = DB::table('school_user_access as access')
            ->join('users', 'users.id', '=', 'access.user_id')
            ->join('schools', 'schools.id', '=', 'access.school_id')
            ->leftJoin('designations', 'designations.id', '=', 'access.designation_id')
            ->whereIn('access.school_id', $schoolIds)
            ->orderBy('users.name')->orderBy('schools.name')
            ->get(['access.id', 'users.name as user_name', 'users.email', 'schools.name as school_name', 'schools.branch_name', 'access.role', 'designations.name as designation_name', 'access.can_view_group']);
        return view('platform.groups.show', [
            'group' => $schoolGroup,
            'directors' => User::whereIn('id', $memberIds)->orderBy('name')->get(),
            'assignments' => $assignments,
            'designations' => Designation::whereIn('school_id', $schoolIds)->with('school:id,name,branch_name')->orderBy('name')->get(),
            'availableSchools' => School::whereNull('school_group_id')->orderBy('name')->get(),
        ]);
    }

    public function addBranch(Request $request, SchoolGroup $schoolGroup): RedirectResponse
    {
        $data = $request->validate(['school_id' => ['required', Rule::exists('schools', 'id')->whereNull('school_group_id')], 'branch_name' => ['nullable', 'string', 'max:100']]);
        School::whereKey($data['school_id'])->update(['school_group_id' => $schoolGroup->id, 'branch_name' => $data['branch_name']]);
        $this->audit($request, 'platform.school_group.branch_added', ['group_id' => $schoolGroup->id, 'school_id' => $data['school_id']]);
        return back()->with('status', 'Branch added to the group.');
    }

    public function grantAccess(Request $request, SchoolGroup $schoolGroup): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')->where('school_group_id', $schoolGroup->id)],
            'role' => ['nullable', Rule::in(['admin', 'teacher', 'bursar', 'academic_admin', 'registrar'])],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'can_view_group' => ['nullable', 'boolean'],
        ]);
        $user = User::where('email', $data['email'])->firstOrFail();
        abort_unless($schoolGroup->schools()->whereKey($user->getRawOriginal('school_id'))->exists(), 422, 'The staff member must already belong to one branch in this group.');

        if (empty($data['school_id'])) {
            foreach ($schoolGroup->schools as $school) {
                DB::table('school_user_access')->updateOrInsert(['school_id' => $school->id, 'user_id' => $user->id], [
                    'role' => 'admin', 'designation_id' => null, 'can_view_group' => true, 'updated_at' => now(), 'created_at' => now(),
                ]);
            }
            $message = $user->name.' can now manage every branch and view group reporting.';
        } else {
            $school = $schoolGroup->schools()->findOrFail($data['school_id']);
            $designationId = $data['designation_id'] ?? null;
            if ($designationId) {
                abort_unless(Designation::whereKey($designationId)->where('school_id', $school->id)->exists(), 422, 'The designation must belong to the selected branch.');
            }
            DB::table('school_user_access')->updateOrInsert(['school_id' => $school->id, 'user_id' => $user->id], [
                'role' => $data['role'] ?? 'teacher',
                'designation_id' => $designationId,
                'can_view_group' => (bool) ($data['can_view_group'] ?? false),
                'updated_at' => now(), 'created_at' => now(),
            ]);
            $message = $user->name.' was assigned to '.($school->branch_name ?: $school->name).' as '.str($data['role'] ?? 'teacher')->replace('_', ' ')->title().'.';
        }
        $this->audit($request, 'platform.school_group.access_granted', ['group_id' => $schoolGroup->id, 'user_id' => $user->id, 'school_id' => $data['school_id'] ?? null, 'role' => $data['role'] ?? 'admin']);
        return back()->with('status', $message);
    }

    private function audit(Request $request, string $event, array $metadata): void
    {
        PlatformAuditLog::create(['platform_admin_id' => Auth::guard('platform')->id(), 'event' => $event, 'metadata' => $metadata, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
    }
}
