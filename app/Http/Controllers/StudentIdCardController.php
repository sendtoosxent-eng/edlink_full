<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentIdCardController extends Controller
{
    public function index(Request $request)
    {
        $school = $request->user()->school;
        $type = $request->string('type')->lower()->value() === 'staff' ? 'staff' : 'student';
        $search = trim($request->string('search')->value());

        if ($type === 'student') {
            $people = Student::with(['schoolClass', 'stream'])->where('school_id', $school->id)->where('status', 'active')
                ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('admission_no', 'like', "%{$search}%")))
                ->when($request->integer('class_id'), fn ($query, $classId) => $query->where('school_class_id', $classId))->orderBy('name')->get();
        } else {
            $people = User::with('designation')->where('school_id', $school->id)->whereNotIn('role', ['parent', 'student'])
                ->when($search, fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('staff_number', 'like', "%{$search}%")))
                ->when($request->string('role')->value(), fn ($query, $role) => $query->where('role', $role))->orderBy('name')->get();
        }

        return view('students.id-cards', [
            'school' => $school, 'type' => $type, 'people' => $people,
            'classes' => SchoolClass::where('school_id', $school->id)->orderBy('sort_order')->orderBy('name')->get(),
            'roles' => User::where('school_id', $school->id)->whereNotIn('role', ['parent', 'student'])->whereNotNull('role')->distinct()->orderBy('role')->pluck('role'),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['student', 'staff'])],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $school = $request->user()->school;

        $people = $data['type'] === 'student'
            ? Student::with(['schoolClass', 'stream', 'guardians'])->where('school_id', $school->id)->where('status', 'active')->whereIn('id', $data['ids'])->orderBy('name')->get()
            : User::with('designation')->where('school_id', $school->id)->whereNotIn('role', ['parent', 'student'])->whereIn('id', $data['ids'])->orderBy('name')->get();

        abort_if($people->isEmpty(), 404);
        $cards = $people->map(fn ($person) => [
            'person' => $person,
            'qr' => $this->qrDataUri("{$school->school_number}|{$data['type']}|{$person->getKey()}|".($person->admission_no ?? $person->staff_number ?? '')),
        ]);

        return Pdf::loadView('students.id-cards-pdf', ['cards' => $cards, 'school' => $school, 'type' => $data['type']])
            ->setPaper('a4')->stream("{$data['type']}-id-cards.pdf");
    }

    private function qrDataUri(string $value): string
    {
        $renderer = new ImageRenderer(new RendererStyle(160, 1), new SvgImageBackEnd);
        return 'data:image/svg+xml;base64,'.base64_encode((new Writer($renderer))->writeString($value));
    }
}
