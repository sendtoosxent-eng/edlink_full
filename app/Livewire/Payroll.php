<?php

namespace App\Livewire;

use App\Models\CashPoolEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Payroll extends Component
{
    public string $period = '';
    public string $search = '';
    public ?int $selectedStaffId = null;
    public string $paymentType = 'salary';
    public string $amount = '';
    public string $method = 'cash';
    public string $transactionId = '';
    public string $bankSlipNumber = '';
    public string $notes = '';
    public string $paidOn = '';

    public function mount(): void
    {
        $this->period = now()->format('Y-m');
        $this->paidOn = now()->toDateString();
        $staffId = request()->integer('staff');
        if ($staffId) {
            $this->selectStaff($staffId);
        }
    }

    public function updatedPeriod(): void
    {
        $this->resetValidation();
        $this->setSuggestedAmount();
    }

    public function updatedPaymentType(): void
    {
        $this->resetValidation();
        $this->setSuggestedAmount();
    }

    public function selectStaff(int $staffId): void
    {
        $staff = User::where('school_id', Auth::user()->school_id)
            ->whereNotIn('role', ['student', 'parent'])
            ->findOrFail($staffId);

        $this->selectedStaffId = $staff->id;
        $this->resetValidation();
        $this->reset(['transactionId', 'bankSlipNumber', 'notes']);
        $this->method = 'cash';
        $this->paymentType = 'salary';
        $this->paidOn = now()->toDateString();
        $this->setSuggestedAmount();
    }

    public function closeProfile(): void
    {
        $this->selectedStaffId = null;
        $this->resetValidation();
    }

    public function recordPayment(): void
    {
        abort_unless(Auth::user()->hasPermission('staff.payroll'), 403);

        $school = Auth::user()->school;
        $term = $school->currentTerm();
        $staff = User::where('school_id', $school->id)
            ->where('employment_status', 'active')
            ->whereNotIn('role', ['student', 'parent'])
            ->findOrFail($this->selectedStaffId);

        $this->validate([
            'period' => ['required', 'date_format:Y-m'],
            'paymentType' => ['required', 'in:salary,advance'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,mobile_money,bank'],
            'transactionId' => [$this->method === 'mobile_money' ? 'required' : 'nullable', 'string', 'max:100'],
            'bankSlipNumber' => [$this->method === 'bank' ? 'required' : 'nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'paidOn' => ['required', 'date'],
        ]);

        $summary = $this->summaryFor($staff);
        if ((float) $this->amount > $summary['remaining']) {
            $this->addError('amount', 'This amount exceeds the UGX '.number_format($summary['remaining']).' remaining for '.$this->period.'.');
            return;
        }

        if ($summary['remaining'] <= 0) {
            $this->addError('amount', 'This employee has already received the full amount due for this period.');
            return;
        }

        DB::transaction(function () use ($school, $term, $staff): void {
            $run = PayrollRun::create([
                'school_id' => $school->id,
                'term_id' => $term?->id,
                'period' => $this->period,
                'user_id' => $staff->id,
                'payment_type' => $this->paymentType,
                'salary_snapshot' => $staff->base_salary,
                'amount' => $this->amount,
                'method' => $this->method,
                'transaction_id' => $this->method === 'mobile_money' ? trim($this->transactionId) : null,
                'bank_slip_number' => $this->method === 'bank' ? trim($this->bankSlipNumber) : null,
                'notes' => trim($this->notes) ?: null,
                'paid_at' => $this->paidOn.' '.now()->format('H:i:s'),
                'recorded_by' => Auth::id(),
            ]);
        });

        $type = $this->paymentType === 'advance' ? 'Salary advance' : 'Salary payment';
        session()->flash('status', $type.' of UGX '.number_format((float) $this->amount).' recorded for '.$staff->name.' and sent for approval.');
        $this->reset(['transactionId', 'bankSlipNumber', 'notes']);
        $this->method = 'cash';
        $this->paymentType = 'salary';
        $this->paidOn = now()->toDateString();
        $this->setSuggestedAmount();
    }

    protected function staffQuery()
    {
        return User::where('school_id', Auth::user()->school_id)
            ->where('employment_status', 'active')
            ->whereNotIn('role', ['student', 'parent'])
            ->when($this->search, fn ($query) => $query->where(function ($scope) {
                $scope->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('staff_number', 'like', '%'.$this->search.'%')
                    ->orWhere('job_title', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name');
    }

    protected function runsFor(User $staff, ?string $period = null): Collection
    {
        return PayrollRun::where('school_id', Auth::user()->school_id)
            ->where('user_id', $staff->id)
            ->when($period, fn ($query) => $query->where('period', $period))
            ->latest('paid_at')
            ->get();
    }

    protected function summaryFor(User $staff): array
    {
        $runs = $this->runsFor($staff, $this->period);
        $salary = (float) $staff->base_salary;
        $advances = (float) $runs->where('payment_type', 'advance')->sum('amount');
        $salaryPaid = (float) $runs->where('payment_type', 'salary')->sum('amount');

        return [
            'salary' => $salary,
            'advances' => $advances,
            'salary_paid' => $salaryPaid,
            'received' => $advances + $salaryPaid,
            'remaining' => max(0, $salary - $advances - $salaryPaid),
        ];
    }

    protected function setSuggestedAmount(): void
    {
        if (! $this->selectedStaffId) {
            $this->amount = '';
            return;
        }

        $staff = User::where('school_id', Auth::user()->school_id)->find($this->selectedStaffId);
        $this->amount = $staff && $this->paymentType === 'salary'
            ? (string) $this->summaryFor($staff)['remaining']
            : '';
    }

    public function render()
    {
        $school = Auth::user()->school;
        $staff = $this->staffQuery()->get();
        $selectedStaff = $this->selectedStaffId
            ? User::where('school_id', $school->id)->find($this->selectedStaffId)
            : null;
        $summary = $selectedStaff ? $this->summaryFor($selectedStaff) : null;
        $employeePayments = $selectedStaff
            ? PayrollRun::with('recordedBy')->where('school_id', $school->id)->where('user_id', $selectedStaff->id)->latest('paid_at')->limit(20)->get()
            : collect();
        $summaries = $staff->mapWithKeys(fn ($member) => [$member->id => $this->summaryFor($member)]);
        $recentPayments = PayrollRun::with(['staff', 'recordedBy'])
            ->where('school_id', $school->id)
            ->latest('paid_at')
            ->limit(20)
            ->get();

        return view('livewire.payroll', [
            'staff' => $staff,
            'selectedStaff' => $selectedStaff,
            'summary' => $summary,
            'summaries' => $summaries,
            'employeePayments' => $employeePayments,
            'recentPayments' => $recentPayments,
            'term' => $school->currentTerm(),
            'pageTitle' => 'Payroll',
        ]);
    }
}
