<?php

namespace App\Mail;

use App\Models\FeePayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FeePayment $payment)
    {
        $this->payment->loadMissing(['school', 'student.schoolClass', 'term', 'recordedBy']);
    }

    public function build(): self
    {
        $school = $this->payment->school;
        $student = $this->payment->student;
        $pdf = Pdf::loadView('pdf.payment-receipt', [
            'payment' => $this->payment,
            'school' => $school,
            'student' => $student,
            'term' => $this->payment->term,
            'pdfMode' => true,
        ])->setPaper('a4', 'portrait');
        $filename = 'receipt-'.($this->payment->transaction_id ?: $this->payment->id).'.pdf';

        return $this->subject($school->name.' - School fees payment received')
            ->view('emails.payment-receipt')
            ->with(['school' => $school, 'student' => $student, 'payment' => $this->payment])
            ->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
    }
}
