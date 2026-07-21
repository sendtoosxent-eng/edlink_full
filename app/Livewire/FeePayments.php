<?php

namespace App\Livewire;

use App\Models\FeePayment;
use App\Models\CashPoolEntry;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FeePayments extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $payingStudentId = null;
    public string $amount = '';
    public string $method = 'cash';
    public string $notes = '';
    public string $transaction_id = '';
    public string $bank_slip_number = '';
    public ?int $deletingPaymentId = null;

    public function mount(): void
    {
        $studentId = request()->integer('student');
        if ($studentId && Student::where('school_id', Auth::user()->school_id)->where('status', 'active')->whereKey($studentId)->exists()) {
            $this->openPaymentForm($studentId);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openPaymentForm(int $studentId): void
    {
        $this->resetValidation();
        $this->reset(['amount', 'method', 'notes', 'transaction_id', 'bank_slip_number']);
        $this->method = 'cash';
        $this->payingStudentId = $studentId;
    }

    public function cancelPayment(): void
    {
        $this->payingStudentId = null;
    }

    public function confirmDelete(int $paymentId): void
    {
        $this->deletingPaymentId = $paymentId;
    }

    public function cancelDelete(): void
    {
        $this->deletingPaymentId = null;
    }

    public function deletePayment(int $paymentId): void
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        $payment = FeePayment::where('school_id', $school->id)->findOrFail($paymentId);

        if (! $term || ! $term->isOpen() || $payment->term_id !== $term->id) {
            session()->flash('error', 'Only payments in the current open term can be deleted.');
            $this->deletingPaymentId = null;
            return;
        }

        DB::transaction(fn () => $payment->delete());
        $this->deletingPaymentId = null;
        session()->flash('status', 'Payment deleted and its credit removed from the cash pool.');
    }

    public function recordPayment(): void
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();

        if (! $term) {
            session()->flash('error', 'No open term — can\'t record a payment.');
            return;
        }

        if (! $term->isEditable()) {
            session()->flash('error', 'This term is locked and can no longer accept payments.');
            return;
        }

        $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,mobile_money,bank,other'],
            'transaction_id' => [$this->method === 'mobile_money' ? 'required' : 'nullable', 'string', 'max:100'],
            'bank_slip_number' => [$this->method === 'bank' ? 'required' : 'nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $student = Student::where('school_id', $school->id)->findOrFail($this->payingStudentId);

        $payment = DB::transaction(function () use ($school, $student, $term) {
            $payment = FeePayment::create([
                'school_id' => $school->id, 'student_id' => $student->id,
                'term_id' => $term->id, 'amount' => $this->amount,
                'method' => $this->method, 'transaction_id' => $this->transaction_id ?: null, 'bank_slip_number' => $this->bank_slip_number ?: null, 'notes' => $this->notes ?: null,
                'recorded_by' => Auth::id(), 'paid_at' => now(),
            ]);

            CashPoolEntry::create([
                'school_id' => $school->id, 'term_id' => $term->id,
                'fee_payment_id' => $payment->id, 'direction' => 'credit',
                'amount' => $payment->amount, 'description' => 'Student fee payment: '.$student->name,
                'transacted_at' => $payment->paid_at, 'recorded_by' => Auth::id(),
            ]);
            return $payment;
        });

        $this->payingStudentId = null;
        session()->flash('status', 'Payment of UGX '.number_format((float) $this->amount).' recorded for '.$student->name.'.');
        $this->dispatch('payment-recorded', receiptUrl: route('fee-payments.receipt', $payment));
    }

    public function render()
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();

        $students = Student::with(['schoolClass', 'category'])
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('admission_no', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->paginate(15);

        $payments = FeePayment::with(['student.schoolClass', 'term', 'recordedBy'])
            ->where('school_id', $school->id)
            ->latest('paid_at')
            ->paginate(10, ['*'], 'paymentsPage');

        return view('livewire.fee-payments', [
            'students' => $students,
            'payments' => $payments,
            'term' => $term,
            'pageTitle' => 'Fee Payments',
        ]);
    }
}
