<?php

use App\Models\DemoRegistration;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\SchoolAcademicSetup;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-school-login')] class extends Component
{
    public int $step = 1;

    // Step 1 — school + admin
    public string $school_name = '';

    public string $school_type = 'primary';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Step 2 — class + stream
    public string $class_name = '';

    public string $stream_name = '';

    // Step 3 — team member
    public string $staff_name = '';

    public string $staff_email = '';

    public string $staff_role = 'teacher'; // teacher | bursar

    public string $staff_password = '';

    public function goToStep2(): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', 'in:primary,secondary,kindergarten'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($this->emailHasActiveDemoClaim()) {
            $this->addError('email', 'This email has already used a free demo. Please log in, or contact us to upgrade.');

            return;
        }

        $this->step = 2;
    }

    public function goToStep3(): void
    {
        $this->validate([
            'class_name' => [$this->school_type === 'kindergarten' ? 'required' : 'nullable', 'string', 'max:255'],
            'stream_name' => ['required', 'string', 'max:255'],
        ]);

        $this->step = 3;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function skipStaffAndFinish(): void
    {
        $this->staff_name = '';
        $this->staff_email = '';
        $this->staff_password = '';
        $this->finish();
    }

    public function finish(): void
    {
        $rateKey = 'demo-registration|'.request()->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            $this->addError('email', 'Too many demo registrations from this connection. Please try again later.');

            return;
        }
        $this->email = Str::lower(trim($this->email));
        $this->staff_email = Str::lower(trim($this->staff_email));

        $this->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', 'in:primary,secondary,kindergarten'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'class_name' => [$this->school_type === 'kindergarten' ? 'required' : 'nullable', 'string', 'max:255'],
            'stream_name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->emailHasActiveDemoClaim()) {
            $this->step = 1;
            $this->addError('email', 'This email has already used a free demo. Please log in, or contact us to upgrade.');

            return;
        }

        if ($this->staff_name || $this->staff_email || $this->staff_password) {
            $this->validate([
                'staff_name' => ['required', 'string', 'max:255'],
                'staff_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
                'staff_role' => ['required', 'in:teacher,bursar'],
                'staff_password' => ['required', 'string', 'min:8'],
            ]);
        }

        $user = DB::transaction(function () {
            $school = School::create([
                'name' => $this->school_name,
                'school_type' => $this->school_type,
                'slug' => Str::slug($this->school_name).'-'.Str::lower(Str::random(5)),
                'status' => 'demo',
                'is_demo' => true,
                'demo_expires_at' => now()->addDays(10),
                'license_plan' => 'basic',
                'license_status' => 'trial',
                'license_started_at' => now(),
                'license_expires_at' => now()->addDays(10),
                'license_student_limit' => \App\Support\SubscriptionPlans::limit('basic'),
            ]);

            $admin = User::create([
                'school_id' => $school->id,
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => 'admin',
            ]);

            app(\App\Services\DefaultDesignationService::class)->ensureFor($school);

            DemoRegistration::updateOrCreate(
                ['email' => $this->email],
                ['used_at' => now()],
            );

            Term::create([
                'school_id' => $school->id,
                'name' => 'Term 1',
                'year' => now()->year,
                'is_current' => true,
            ]);

            SchoolAcademicSetup::provision($school);
            $class = $this->school_type === 'kindergarten'
                ? SchoolClass::create(['school_id' => $school->id, 'name' => $this->class_name, 'education_stage' => 'kindergarten'])
                : $school->classes()->orderBy('sort_order')->firstOrFail();

            $stream = Stream::create([
                'school_id' => $school->id,
                'school_class_id' => $class->id,
                'name' => $this->stream_name,
            ]);

            foreach (['A. Nakato', 'B. Kato', 'C. Mugisha'] as $i => $studentName) {
                Student::create([
                    'school_id' => $school->id,
                    'school_class_id' => $class->id,
                    'stream_id' => $stream->id,
                    'name' => $studentName,
                    'admission_no' => 'DEMO-'.$class->id.'-'.($i + 1),
                ]);
            }

            if ($this->staff_email) {
                User::create([
                    'school_id' => $school->id,
                    'name' => $this->staff_name,
                    'email' => $this->staff_email,
                    'password' => Hash::make($this->staff_password),
                    'role' => $this->staff_role,
                ]);
            }

            return $admin;
        });

        event(new Registered($user));
        RateLimiter::hit($rateKey, 3600);
        Auth::login($user);

        session()->flash('new_school_number', $user->school->school_number);

        $this->redirect(route('verification.notice', absolute: false), navigate: true);
    }

    public function getSideImageProperty(): string
    {
        return match ($this->step) {
            1 => 'reg.png',
            2 => 'girl-with-books.png',
            3 => 'discussion.png',
            default => 'teacher-explaining.png',
        };
    }

    private function emailHasActiveDemoClaim(): bool
    {
        return DemoRegistration::where('email', $this->email)->exists()
            && User::where('email', $this->email)->exists();
    }
}; ?>

<div class="school-registration">
    <div class="registration-progress-label"><span>Your new school workspace</span><strong>Step {{ $step }} of 3</strong></div>
    <ol class="registration-steps" aria-label="Registration progress">
        @foreach([1 => ['School & account', 'Your details'], 2 => ['Classes & streams', 'School setup'], 3 => ['Your team', 'Ready to launch']] as $number => [$label, $caption])
            <li class="{{ $step === $number ? 'is-current' : ($step > $number ? 'is-complete' : '') }}" @if($step === $number) aria-current="step" @endif>
                <span class="registration-step-number" aria-hidden="true">@if($step > $number)<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg>@else{{ $number }}@endif</span>
                <span class="registration-step-label">{{ $label }}<small>{{ $caption }}</small></span>
                <span class="registration-sr">{{ $step > $number ? 'Completed' : ($step === $number ? 'Current step' : 'Upcoming') }}</span>
            </li>
        @endforeach
    </ol>

    <div class="registration-heading" aria-live="polite">
        <span class="registration-demo-badge">Free 10-day demo</span>
        <h1>{{ match ($step) { 1 => 'Bring your school together.', 2 => 'Make room for learning.', 3 => 'Better with your team.' } }}</h1>
        <p>{{ match ($step) { 1 => 'Start with your school and administrator account.', 2 => 'Set up your first stream. You can add more from your dashboard.', 3 => 'Add a teacher or bursar, or invite your team later.' } }}</p>
    </div>

    @if($errors->any())<div class="registration-errors" role="alert"><strong>Please check your details.</strong>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <div wire:key="registration-step-{{ $step }}">
    @if($step === 1)
        <form wire:submit="goToStep2" class="registration-form">
            <div class="registration-field registration-wide"><label for="register-school-name">School name</label><input id="register-school-name" name="school_name" wire:model="school_name" type="text" required autofocus autocomplete="organization" maxlength="255" placeholder="e.g. St Monica Primary School"></div>
            <div class="registration-field"><label for="register-school-type">School type</label><select id="register-school-type" name="school_type" wire:model.live="school_type" required><option value="primary">Primary school</option><option value="secondary">Secondary school</option><option value="kindergarten">Kindergarten</option></select></div>
            <div class="registration-field"><label for="register-name">Your full name</label><input id="register-name" name="name" wire:model="name" type="text" required autocomplete="name" maxlength="255" placeholder="e.g. Sarah Namubiru"></div>
            <div class="registration-field registration-wide"><label for="register-email">Your email address</label><input id="register-email" name="email" wire:model="email" type="email" required autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="255" placeholder="you@school.com"></div>
            <div class="registration-field"><label for="register-password">Password</label><input id="register-password" name="password" wire:model="password" type="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters"></div>
            <div class="registration-field"><label for="register-password-confirmation">Confirm password</label><input id="register-password-confirmation" name="password_confirmation" wire:model="password_confirmation" type="password" required minlength="8" autocomplete="new-password" placeholder="Repeat your password"></div>
            <p class="registration-hint registration-wide">Your school type determines its standard classes, grading and reports.</p>
            <button type="submit" class="registration-primary registration-wide" wire:loading.attr="disabled" wire:target="goToStep2"><span wire:loading.remove wire:target="goToStep2">Continue to school setup</span><span wire:loading wire:target="goToStep2">Checking details…</span><span aria-hidden="true">→</span></button>
        </form>
    @elseif($step === 2)
        <form wire:submit="goToStep3" class="registration-form">
            @if($school_type === 'kindergarten')
                <div class="registration-field registration-wide"><label for="register-class-name">First class name</label><input id="register-class-name" name="class_name" wire:model="class_name" required autofocus maxlength="255" placeholder="e.g. Baby Class"></div>
            @else
                <div class="registration-notice registration-wide"><strong>Your standard classes are included.</strong><p>{{ $school_type === 'primary' ? 'Primary 1–7' : 'Senior 1–6' }} will be created automatically and protected from deletion.</p></div>
            @endif
            <div class="registration-field registration-wide"><label for="register-stream-name">First stream name</label><input id="register-stream-name" name="stream_name" wire:model="stream_name" type="text" required maxlength="255" placeholder="e.g. Blue, North or Stream A"></div>
            <p class="registration-hint registration-wide">We’ll include a few sample learners so you can explore the workspace.</p>
            <div class="registration-actions registration-wide"><button type="button" wire:click="back" class="registration-secondary" wire:loading.attr="disabled">← Back</button><button type="submit" class="registration-primary" wire:loading.attr="disabled" wire:target="goToStep3,back"><span wire:loading.remove wire:target="goToStep3">Continue to your team</span><span wire:loading wire:target="goToStep3">Saving details…</span><span aria-hidden="true">→</span></button></div>
        </form>
    @else
        <form wire:submit="finish" class="registration-form">
            <div class="registration-field"><label for="register-staff-name">Team member’s name</label><input id="register-staff-name" name="staff_name" wire:model="staff_name" type="text" autofocus maxlength="255" placeholder="e.g. Daniel Okello"></div>
            <div class="registration-field"><label for="register-staff-role">Their role</label><select id="register-staff-role" name="staff_role" wire:model="staff_role"><option value="teacher">Teacher</option><option value="bursar">Bursar</option></select></div>
            <div class="registration-field registration-wide"><label for="register-staff-email">Their email address</label><input id="register-staff-email" name="staff_email" wire:model="staff_email" type="email" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="255" placeholder="daniel@school.com"></div>
            <div class="registration-field registration-wide"><label for="register-staff-password">Temporary password</label><input id="register-staff-password" name="staff_password" wire:model="staff_password" type="password" minlength="8" autocomplete="new-password" placeholder="At least 8 characters"></div>
            <div class="registration-actions registration-wide"><button type="button" wire:click="back" class="registration-secondary" wire:loading.attr="disabled">← Back</button><button type="submit" class="registration-primary" wire:loading.attr="disabled" wire:target="finish,skipStaffAndFinish,back"><span wire:loading.remove wire:target="finish">Launch workspace</span><span wire:loading wire:target="finish">Creating workspace…</span><span aria-hidden="true">→</span></button></div>
        </form>
        <button type="button" wire:click="skipStaffAndFinish" wire:loading.attr="disabled" wire:target="finish,skipStaffAndFinish,back" class="registration-skip"><span wire:loading.remove wire:target="skipStaffAndFinish">Skip for now — add my team later</span><span wire:loading wire:target="skipStaffAndFinish">Creating workspace…</span></button>
    @endif
    </div>
    <p class="registration-login">Already have an account? <a href="{{ route('login') }}" wire:navigate>Log in to Edlink</a></p>
</div>
