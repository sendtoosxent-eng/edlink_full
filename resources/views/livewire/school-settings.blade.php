<div class="space-y-6" x-data="{tab:'profile'}">
    <!-- HEADER -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-amber-300">System settings</h1>
            
            <p class="text-xs text-slate-500 mt-0.5">Settings apply only to your school and affect formal documents, rules, and portal behavior.</p>
        </div>
        <br>
        <button type="submit" form="settings-form" class="inline-flex items-center justify-center gap-1.5 bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-bold text-xs px-5 py-2.5 rounded-xl transition shadow-sm">
            <i class="fa fa-save text-[11px]"></i>
            <span>Save all settings</span>
        </button>

        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    @if(session('status'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3.5 text-xs font-semibold text-emerald-800 flex items-center gap-2 shadow-2xs">
            <i class="fa fa-check-circle text-emerald-500"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form wire:submit="save" id="settings-form" class="space-y-6">
        <!-- NAVIGATION TABS -->
        <div class="flex flex-wrap gap-2 bg-slate-100/70 p-1.5 rounded-2xl border border-slate-200/60">
            @foreach([
                'profile' => ['label' => 'School profile', 'icon' => 'fa-school'],
                'academic' => ['label' => 'Academic', 'icon' => 'fa-graduation-cap'],
                'documents' => ['label' => 'Certificates', 'icon' => 'fa-certificate'],
                'finance' => ['label' => 'Finance', 'icon' => 'fa-wallet'],
                'staff' => ['label' => 'Staff', 'icon' => 'fa-users'],
                'communication' => ['label' => 'Communication', 'icon' => 'fa-bullhorn'],
                'security' => ['label' => 'Security', 'icon' => 'fa-shield']
            ] as $key => $tabData)
                <button type="button" @click="tab='{{$key}}'" 
                    :class="tab==='{{$key}}' ? 'bg-yellow-400 text-slate-950 shadow-xs' : 'bg-transparent text-slate-600 hover:text-slate-900 hover:bg-white/50'" 
                    class="flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition">
                    <i class="fa {{$tabData['icon']}} text-[11px]"></i>
                    <span>{{$tabData['label']}}</span>
                </button>
            @endforeach
        </div>

        <!-- 1. PROFILE SECTION -->
        <section x-show="tab==='profile'" class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400">
            <div class="mb-5 pb-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">School profile</h2>
                <p class="text-xs text-slate-500 mt-0.5">Appears on report cards, receipts, and official communications.</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">School name</label>
                    <input wire:model="name" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Official school name">
                    <span class="block text-[11px] text-slate-400 mt-1">Official school name displayed across modules.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">School number</label>
                    <input value="{{auth()->user()->school->school_number}}" disabled class="w-full text-xs px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 font-mono cursor-not-allowed">
                    <span class="block text-[11px] text-slate-400 mt-1">Permanent Edlink identifier (read-only).</span>
                </div>
               <div>
    <label class="block text-xs font-semibold text-slate-700 mb-2">Badge/logo</label>
    
    <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-2xl border border-slate-200">
        <!-- CIRCULAR BADGE/LOGO CONTAINER -->
        <div class="relative w-14 h-14 rounded-full border-2 border-yellow-400 bg-white flex-shrink-0 overflow-hidden shadow-xs flex items-center justify-center">
            @if ($badge && method_exists($badge, 'temporaryUrl'))
                <img src="{{ $badge->temporaryUrl() }}" class="w-full h-full object-cover">
            @elseif($currentBadgeUrl)
                <img src="{{ $currentBadgeUrl }}" class="w-full h-full object-cover" alt="Current school badge">
            @else
                <div class="w-full h-full flex items-center justify-center text-slate-400 bg-slate-100">
                    <i class="fa fa-university text-lg"></i>
                </div>
            @endif
        </div>

        <!-- INPUT CONTROL & HELP TEXT -->
        <div class="flex-1 min-w-0">
            <input wire:model="badge" type="file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer transition">
            @if($currentBadgeUrl)<button type="button" wire:click="removeBadge" wire:confirm="Remove the current school badge?" class="mt-2 text-[11px] font-bold text-rose-600">Remove current badge</button>@endif
            @error('badge')<span class="block text-[11px] text-rose-600 mt-1">{{$message}}</span>@enderror
            <div wire:loading wire:target="badge" class="mt-1 text-[11px] text-amber-700">Loading badge preview…</div>
            <span class="block text-[11px] text-slate-400 mt-1">Used on formal documents and reports.</span>
        </div>
    </div>
</div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Motto</label>
                    <input wire:model="motto" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. Education is power">
                    <span class="block text-[11px] text-slate-400 mt-1">Shown beneath the school name.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Principal/head teacher</label>
                    <input wire:model="principal_name" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Full name">
                    <span class="block text-[11px] text-slate-400 mt-1">Official school leader.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Website</label>
                    <input wire:model="website" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="https://...">
                    <span class="block text-[11px] text-slate-400 mt-1">Public school website.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone</label>
                    <input wire:model="phone" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="+256...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
                    <input wire:model="email" type="email" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="school@domain.com">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Address</label>
                    <input wire:model="address" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Physical location / P.O. Box">
                </div>
            </div>
        </section>

        <!-- 2. ACADEMIC SECTION -->
        <section x-show="tab==='academic'" class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400" style="display: none;">
            <div class="mb-5 pb-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Academic & student rules</h2>
                <p class="text-xs text-slate-500 mt-0.5">These guide term operations, promotion, and registration policies.</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Academic year/term rule</label>
                    <select wire:model="settings.academic_year_rule" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option>Three terms per academic year</option><option>Two semesters per academic year</option><option>Four quarters per academic year</option><option>Custom academic calendar</option></select>
                    <span class="block text-[11px] text-slate-400 mt-1">Defines term cycles and transitions.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Promotion rule</label>
                    <select wire:model="settings.promotion_rule" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="automatic_average">Automatic using average pass mark</option><option value="average_attendance">Average pass mark plus attendance</option><option value="manual_review">Manual academic review</option><option value="disabled">Promotions disabled</option></select>
                    <span class="block text-[11px] text-slate-400 mt-1">Used during end-of-term promotion processing.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Maximum progression</label>
                    <input wire:model="settings.max_class_progression" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. S.6">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Required registration fields</label>
                    <select wire:model="settings.required_student_fields" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="basic">Basic: name, gender, class and admission number</option><option value="standard">Standard: basic plus guardian and address</option><option value="comprehensive">Comprehensive: all personal, guardian and medical fields</option></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Student status rules</label>
                    <select wire:model="settings.student_status_rules" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="standard_lifecycle">Standard: active, inactive, withdrawn and graduated</option><option value="term_driven">Status follows term enrolment automatically</option><option value="admin_controlled">Status changed by administrators only</option></select>
                    <span class="block text-[11px] text-slate-400 mt-1">Active, inactive, withdrawn, graduated rules.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Grading scale note</label>
                    <select wire:model="settings.grading_scale" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="stage_scales">Use saved education-stage grading scales</option><option value="grades_aggregates">Show grades and aggregates</option><option value="grades_averages">Show grades and averages</option><option value="competency_descriptors">Use competency descriptors</option></select>
                    <span class="block text-[11px] text-slate-400 mt-1">Actual grade bands are managed in Grading Scales.</span>
                </div>
            </div>
        </section>

        <!-- CERTIFICATES & RECOMMENDATIONS -->
        <section x-show="tab==='documents'" class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400" style="display: none;">
            <div class="mb-5 border-b border-slate-100 pb-4">
                <h2 class="text-sm font-bold text-slate-900">Certificates & recommendations</h2>
                <p class="mt-0.5 text-xs text-slate-500">Customize the wording and signatory used on graduate documents. The school badge and profile details are added automatically.</p>
            </div>
            <div class="grid gap-6 xl:grid-cols-2">
                <div class="space-y-4 rounded-2xl border border-amber-200 bg-amber-50/40 p-5">
                    <div><h3 class="text-xs font-black uppercase tracking-wider text-amber-800">Graduation certificate</h3><p class="mt-1 text-[11px] text-slate-500">Navy-and-gold landscape design.</p></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-slate-700">Certificate title<input wire:model="settings.certificate_title" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs"><span class="text-rose-600">@error('settings.certificate_title'){{ $message }}@enderror</span></label>
                        <label class="text-xs font-semibold text-slate-700">Subtitle<input wire:model="settings.certificate_subtitle" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs"></label>
                    </div>
                    <label class="block text-xs font-semibold text-slate-700">Presentation line<input wire:model="settings.certificate_intro" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs"></label>
                    <label class="block text-xs font-semibold text-slate-700">Achievement wording<textarea wire:model="settings.certificate_achievement" rows="3" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs"></textarea></label>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-1">Number prefix<input wire:model="settings.certificate_number_prefix" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs" placeholder="CERT"></label>
                        <label class="text-xs font-semibold text-slate-700">Signatory<input wire:model="settings.certificate_signatory_name" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs" placeholder="Head teacher name"></label>
                        <label class="text-xs font-semibold text-slate-700">Designation<input wire:model="settings.certificate_signatory_title" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs" placeholder="Head Teacher"></label>
                    </div>
                </div>
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div><h3 class="text-xs font-black uppercase tracking-wider text-slate-700">Recommendation letter</h3><p class="mt-1 text-[11px] text-slate-500">Formal A4 letter generated for each graduate.</p></div>
                    <label class="block text-xs font-semibold text-slate-700">Document title<input wire:model="settings.recommendation_title" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs"></label>
                    <label class="block text-xs font-semibold text-slate-700">Salutation<input wire:model="settings.recommendation_intro" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs"></label>
                    <label class="block text-xs font-semibold text-slate-700">Main recommendation<textarea wire:model="settings.recommendation_body" rows="5" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs"></textarea></label>
                    <label class="block text-xs font-semibold text-slate-700">Closing endorsement<textarea wire:model="settings.recommendation_closing" rows="3" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs"></textarea></label>
                    <p class="rounded-xl bg-white p-3 text-[11px] leading-5 text-slate-500">Graduate name, class, completion year, final average, certificate number, school contacts, and signatory are inserted automatically.</p>
                </div>
            </div>
        </section>

        <!-- 3. FINANCE SECTION -->
        <section x-show="tab==='finance'" class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400" style="display: none;">
            <div class="mb-5 pb-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Finance & receipts</h2>
                <p class="text-xs text-slate-500 mt-0.5">Controls payment information, financial policy, and document text.</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Currency</label>
                    <input wire:model="settings.currency" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. UGX">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Number format</label>
                    <input wire:model="settings.number_format" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. 0,000.00">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment methods</label>
                    <div x-data="{chosen:@js(array_values(array_filter(explode(',', $settings['payment_methods'] ?? ''))))}" x-effect="$wire.set('settings.payment_methods', chosen.join(','))" class="grid grid-cols-2 gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        @foreach(['cash'=>'Cash','mobile_money'=>'Mobile money','bank'=>'Bank transfer','cheque'=>'Cheque','card'=>'Card / POS','online'=>'Online payment'] as $value=>$label)
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700"><input x-model="chosen" type="checkbox" value="{{$value}}" class="rounded border-slate-300 text-yellow-500 focus:ring-yellow-400">{{$label}}</label>
                        @endforeach
                    </div>
                    <span class="block text-[11px] text-slate-400 mt-1">Check every payment channel accepted by the school.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Admission prefix</label>
                    <input wire:model="settings.admission_prefix" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. SCH/ADM/">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Opening cash-pool balance</label>
                    <input wire:model="settings.cash_pool_opening_balance" type="number" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payroll approval rule</label>
                    <select wire:model="settings.payroll_approval_rule" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="admin">Administrator approval</option><option value="bursar_admin">Bursar then administrator</option><option value="headteacher">Head teacher approval</option><option value="none">No additional approval</option></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mobile money details</label>
                    <textarea wire:model="settings.mobile_money_details" rows="2" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Merchant codes or numbers..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bank details</label>
                    <textarea wire:model="settings.bank_details" rows="2" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Account names and numbers..."></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Receipt footer/signatories</label>
                    <textarea wire:model="settings.receipt_footer" rows="2" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Footer text on official receipts..."></textarea>
                </div>
            </div>
        </section>

        <!-- 4. STAFF SECTION -->
        <section x-show="tab==='staff'" class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400" style="display: none;">
            <div class="mb-5 pb-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Staff settings</h2>
                <p class="text-xs text-slate-500 mt-0.5">Staff numbering, roles, payroll, and leave policy.</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Staff number format</label>
                    <input wire:model="settings.staff_number_format" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. STF/">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Roles and permissions</label>
                    <textarea wire:model="settings.roles_permissions" rows="3" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Permissions config..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payroll period</label>
                    <select wire:model="settings.payroll_period" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="Weekly">Weekly</option><option value="Bi-weekly">Bi-weekly</option><option value="Monthly">Monthly</option><option value="Termly">Termly</option></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Leave types and approvals</label>
                    <select wire:model="settings.leave_types" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="standard">Standard: annual, sick, maternity, paternity and compassionate</option><option value="basic">Basic: annual and sick leave</option><option value="extended">Extended: standard plus study and unpaid leave</option></select>
                    <label class="mt-3 block text-xs font-semibold text-slate-700 mb-1.5">Leave approval route</label>
                    <select wire:model="settings.leave_approval_rule" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="admin">Administrator approval</option><option value="supervisor_admin">Supervisor then administrator</option><option value="headteacher">Head teacher approval</option></select>
                </div>
            </div>
        </section>

        <!-- 5. COMMUNICATION SECTION -->
        <section x-show="tab==='communication'" class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400" style="display: none;">
            <div class="mb-5 pb-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Communication</h2>
                <p class="text-xs text-slate-500 mt-0.5">Controls parent notices and future email/SMS integration.</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sender name</label>
                    <input wire:model="settings.sender_name" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="School Sender ID">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email/SMS provider</label>
                    <textarea wire:model="settings.email_provider" rows="3" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Gateway configuration..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Arrears reminder template</label>
                    <textarea wire:model="settings.arrears_email_template" rows="3" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Template body..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Notification schedule</label>
                    <input wire:model="settings.notification_schedule" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. Daily at 8:00 AM">
                </div>
            </div>
        </section>

        <!-- 6. SECURITY SECTION -->
        <section x-show="tab==='security'" class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400" style="display: none;">
            <div class="mb-5 pb-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Security & system</h2>
                <p class="text-xs text-slate-500 mt-0.5">Controls access, retention, backups, and regional formatting.</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">OTP login</label>
                    <select wire:model="settings.otp_enabled" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900">
                        <option value="enabled">Enabled</option>
                        <option value="disabled">Disabled</option>
                        <option value="admins_only">Required for administrators only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Audit retention</label>
                    <select wire:model="settings.audit_retention" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="3 months">3 months</option><option value="6 months">6 months</option><option value="12 months">12 months</option><option value="24 months">24 months</option><option value="60 months">5 years</option><option value="indefinite">Keep indefinitely</option></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Backup/export rule</label>
                    <textarea wire:model="settings.backup_export_rule" rows="3" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="Backup schedules..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Timezone</label>
                    <select wire:model="settings.timezone" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="Africa/Kampala">Africa/Kampala</option><option value="Africa/Nairobi">Africa/Nairobi</option><option value="Africa/Dar_es_Salaam">Africa/Dar es Salaam</option><option value="UTC">UTC</option></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Date format</label>
                    <select wire:model="settings.date_format" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="d M Y">31 Dec 2026</option><option value="d/m/Y">31/12/2026</option><option value="m/d/Y">12/31/2026</option><option value="Y-m-d">2026-12-31</option></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Language</label>
                    <select wire:model="settings.language" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium"><option value="English">English</option><option value="Luganda">Luganda</option><option value="Swahili">Swahili</option><option value="French">French</option></select>
                </div>
            </div>
        </section>

        <!-- BOTTOM SAVE BAR -->
        <div class="flex items-center justify-end bg-slate-800 border border-slate-200 p-4 rounded-2xl shadow-sm">
            <button type="submit" class="inline-flex items-center justify-center gap-1.5 bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-bold text-xs px-6 py-2.5 rounded-xl transition shadow-sm">
                <i class="fa fa-save text-[11px]"></i>
                <span>Save all settings</span>
            </button>
        </div>
    </form>
</div>
