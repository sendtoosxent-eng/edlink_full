<?php

namespace App\Http\Controllers;

use App\Models\FinanceLedgerEntry;
use App\Models\FinanceReconciliation;
use App\Models\FinancialAccount;
use App\Models\FinancialAccountTransfer;
use App\Models\AuditLog;
use App\Models\PrivacyRequest;
use App\Models\Student;
use App\Models\SystemBackup;
use App\Services\FinanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolOperationsController extends Controller
{
    private function authorizeLedgerAccess(): void
    {
        abort_unless(auth()->user()?->hasPermission('finance.ledger'), 403);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);
    }

    public function finance()
    {
        $this->authorizeLedgerAccess();
        $schoolId = auth()->user()->school_id;

        return view('operations.finance', [
            'entries' => FinanceLedgerEntry::where('school_id', $schoolId)->latest()->paginate(30),
            'reconciliations' => FinanceReconciliation::where('school_id', $schoolId)->latest('period_ending')->limit(12)->get(),
            'accounts' => FinancialAccount::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(),
            'transfers' => FinancialAccountTransfer::with(['fromAccount','toAccount'])->where('school_id', $schoolId)->latest()->limit(20)->get(),
        ]);
    }

    public function approve(FinanceLedgerEntry $entry, FinanceLedgerService $ledger)
    {
        $this->authorizeLedgerAccess();
        abort_unless($entry->school_id === auth()->user()->school_id, 404);
        $ledger->approve($entry, auth()->id());
        AuditLog::record($entry->school_id, 'finance.ledger.approved', $entry);

        return back()->with('status', 'Ledger entry approved.');
    }

    public function reject(Request $request, FinanceLedgerEntry $entry, FinanceLedgerService $ledger)
    {
        $this->authorizeLedgerAccess();
        abort_unless($entry->school_id === auth()->user()->school_id, 404);
        $data = $request->validate(['reason' => 'required|string|min:8|max:500']);
        $ledger->reject($entry, $data['reason'], auth()->id());
        AuditLog::record($entry->school_id, 'finance.ledger.rejected', $entry, ['reason' => $data['reason']]);
        return back()->with('status', 'Transaction rejected; it did not enter the cash pool.');
    }

    public function reverse(Request $request, FinanceLedgerEntry $entry, FinanceLedgerService $ledger)
    {
        $this->authorizeLedgerAccess();
        abort_unless($entry->school_id === auth()->user()->school_id, 404);
        $data = $request->validate(['reason' => 'required|string|min:8|max:500']);
        $reversal = $ledger->reverse($entry, $data['reason'], auth()->id());
        AuditLog::record($entry->school_id, 'finance.ledger.reversed', $reversal, ['original_entry_id' => $entry->id, 'reason' => $data['reason']]);

        return back()->with('status', 'Transaction reversed with an immutable audit entry.');
    }

    public function reconcile(Request $request, FinanceLedgerService $ledger)
    {
        $this->authorizeLedgerAccess();
        $data = $request->validate([
            'financial_account_id' => 'required|integer',
            'period_ending' => 'required|date',
            'statement_balance' => 'required|numeric',
            'notes' => 'nullable|string|max:1000',
        ]);
        $item = $ledger->reconcile(auth()->user()->school_id, (int) $data['financial_account_id'], $data['period_ending'], (float) $data['statement_balance'], $data['notes'] ?? null, auth()->id());
        AuditLog::record($item->school_id, 'finance.reconciliation.closed', $item, ['difference' => $item->difference]);

        return back()->with('status', 'Reconciliation saved.');
    }

    public function storeAccount(Request $request)
    {
        $this->authorizeLedgerAccess();
        $data = $request->validate(['name'=>'required|string|max:100','type'=>'required|in:cash,bank,mobile_money,petty_cash','opening_balance'=>'required|numeric']);
        $account = FinancialAccount::create($data + ['school_id'=>auth()->user()->school_id,'currency'=>'UGX']);
        AuditLog::record($account->school_id, 'finance.account.created', $account);
        return back()->with('status', 'Financial account created.');
    }

    public function storeTransfer(Request $request)
    {
        $this->authorizeLedgerAccess();
        $data=$request->validate(['from_account_id'=>'required|different:to_account_id','to_account_id'=>'required','amount'=>'required|numeric|min:0.01','transfer_date'=>'required|date','reference'=>'nullable|string|max:100','notes'=>'nullable|string|max:500']);
        $school=auth()->user()->school_id;
        foreach (['from_account_id','to_account_id'] as $key) abort_unless(FinancialAccount::where('school_id',$school)->whereKey($data[$key])->exists(),404);
        $transfer=FinancialAccountTransfer::create($data+['school_id'=>$school,'status'=>'pending','recorded_by'=>auth()->id()]);
        AuditLog::record($school,'finance.transfer.recorded',$transfer);
        return back()->with('status','Transfer recorded and awaiting approval by another user.');
    }

    public function approveTransfer(FinancialAccountTransfer $transfer, FinanceLedgerService $ledger)
    {
        $this->authorizeLedgerAccess();
        abort_unless($transfer->school_id===auth()->user()->school_id,404);
        $ledger->approveTransfer($transfer,auth()->id());
        AuditLog::record($transfer->school_id,'finance.transfer.approved',$transfer);
        return back()->with('status','Transfer approved and both account balances updated.');
    }

    public function reopenReconciliation(Request $request, FinanceReconciliation $reconciliation, FinanceLedgerService $ledger)
    {
        $this->authorizeLedgerAccess();
        abort_unless($reconciliation->school_id===auth()->user()->school_id,404);
        $data=$request->validate(['reason'=>'required|string|min:8|max:500']);
        $ledger->reopen($reconciliation,$data['reason'],auth()->id());
        AuditLog::record($reconciliation->school_id,'finance.reconciliation.reopened',$reconciliation,['reason'=>$data['reason']]);
        return back()->with('status','Reconciliation reopened. It may now be corrected and closed again.');
    }

    public function privacy()
    {
        $this->authorizeAdmin();

        return view('operations.privacy', [
            'requests' => PrivacyRequest::where('school_id', auth()->user()->school_id)->latest()->paginate(20),
            'students' => Student::where('school_id', auth()->user()->school_id)->orderBy('name')->get(),
        ]);
    }

    public function createPrivacyRequest(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'type' => 'required|in:export,deletion',
            'student_id' => 'nullable|integer',
            'reason' => 'required|string|max:1000',
            'password' => 'required|string',
        ]);
        abort_unless(Hash::check($data['password'], auth()->user()->password), 422, 'Password confirmation failed.');
        $student = isset($data['student_id']) ? Student::where('school_id', auth()->user()->school_id)->findOrFail($data['student_id']) : null;
        $token = Str::upper(Str::random(10));
        $privacy = PrivacyRequest::create([
            'school_id' => auth()->user()->school_id,
            'requested_by' => auth()->id(),
            'subject_type' => $student ? Student::class : 'school',
            'subject_id' => $student?->id,
            'request_type' => $data['type'],
            'status' => 'verified',
            'reason' => $data['reason'],
            'verification_token_hash' => hash('sha256', $token),
            'verified_at' => now(),
        ]);

        return back()->with('status', "Privacy request #{$privacy->id} verified. Confirmation code: {$token}");
    }

    public function executePrivacyRequest(Request $request, PrivacyRequest $privacyRequest)
    {
        $this->authorizeAdmin();
        abort_unless($privacyRequest->school_id === auth()->user()->school_id && $privacyRequest->status === 'verified', 404);
        $data = $request->validate(['confirmation_code' => 'required|string']);
        abort_unless(hash_equals($privacyRequest->verification_token_hash, hash('sha256', $data['confirmation_code'])), 422, 'Invalid confirmation code.');

        if ($privacyRequest->request_type === 'export') {
            $payload = $this->exportPayload($privacyRequest);
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $privacyRequest->update(['status' => 'completed', 'result' => ['delivered_at' => now()->toISOString()], 'completed_at' => now()]);

            return response()->streamDownload(
                static fn () => print($json),
                'edlink-privacy-export.json',
                ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store, private'],
            );
        }

        abort_unless($privacyRequest->subject_type === Student::class && $privacyRequest->subject_id, 422, 'Deletion requests must identify a student.');
        $photoPath = DB::transaction(function () use ($privacyRequest): ?string {
            $student = Student::where('school_id', $privacyRequest->school_id)->findOrFail($privacyRequest->subject_id);
            $photoPath = $student->photo_path;
            $suffix = 'deleted-'.$student->id;
            $student->update([
                'name' => 'Deleted Student '.$student->id,
                'admission_no' => $suffix,
                'home_address' => null,
                'medical_notes' => null,
                'photo_path' => null,
                'status' => 'inactive',
            ]);
            $student->guardians()->update(['name' => 'Deleted Guardian', 'email' => null, 'phone' => null, 'address' => null]);
            $privacyRequest->update(['status' => 'completed', 'result' => ['anonymized_student_id' => $student->id], 'completed_at' => now()]);

            return $photoPath;
        });
        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return back()->with('status', 'Personal identifiers were removed. Financial and audit records were retained.');
    }

    public function backups()
    {
        $this->authorizeAdmin();

        return view('operations.backups', ['backups' => SystemBackup::latest()->paginate(30)]);
    }

    private function exportPayload(PrivacyRequest $privacyRequest): array
    {
        if ($privacyRequest->subject_type === Student::class && $privacyRequest->subject_id) {
            return Student::with(['guardians', 'schoolClass', 'stream'])->where('school_id', $privacyRequest->school_id)->findOrFail($privacyRequest->subject_id)->toArray();
        }

        $payload = [];
        foreach (['schools', 'students', 'student_guardians', 'fee_payments', 'expenses', 'attendance_records', 'audit_logs'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'school_id')) {
                $payload[$table] = DB::table($table)->where('school_id', $privacyRequest->school_id)->get();
            }
        }
        $payload['users'] = DB::table('users')
            ->where('school_id', $privacyRequest->school_id)
            ->get([
                'id', 'school_id', 'designation_id', 'name', 'email', 'email_verified_at',
                'staff_number', 'phone', 'job_title', 'role', 'employment_status',
                'joined_at', 'created_at', 'updated_at',
            ]);

        return $payload;
    }
}




