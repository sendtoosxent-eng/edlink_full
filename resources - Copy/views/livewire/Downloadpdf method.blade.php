// Add this method to your report's Livewire component class.
// It reuses the same $student / $term / $attendance / $examResults data
// your render() method already builds for the on-screen view — plug in
// whatever query logic currently produces those inside render().

use Barryvdh\DomPDF\Facade\Pdf;

public function downloadPdf()
{
    $student = $this->student; // however you currently resolve this
    $term = $this->term;
    $attendance = $this->attendance; // same collection used on screen
    $examResults = $this->examResults;

    $attendanceCount = $attendance->count();
    $presentCount = $attendance->whereIn('status', ['present', 'late'])->count();
    $absentCount = $attendance->where('status', 'absent')->count();
    $attendancePct = $attendanceCount ? round($presentCount / $attendanceCount * 100, 1) : 0;

    $pdf = Pdf::loadView('pdf.student-term-report', [
        'school' => auth()->user()->school,
        'student' => $student,
        'term' => $term,
        'examResults' => $examResults,
        'attendancePct' => $attendancePct,
        'presentCount' => $presentCount,
        'absentCount' => $absentCount,
        'totalDue' => $student->totalDue($term),
        'totalPaid' => $student->totalPaid($term),
        'balance' => $student->balance($term),
    ]);

    $filename = str($student->name)->slug().'-term-report-'.str($term->name.'-'.$term->year)->slug().'.pdf';

    return response()->streamDownload(
        fn () => print($pdf->output()),
        $filename
    );
}