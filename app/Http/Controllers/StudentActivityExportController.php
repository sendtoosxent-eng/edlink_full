<?php

namespace App\Http\Controllers;

use App\Support\CsvSafe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentActivityExportController extends Controller
{
    public function __invoke(Request $request, string $type, int $activity): StreamedResponse
    {
        abort_unless(in_array($type, ['house', 'club'], true), 404);
        $user = $request->user();
        $isManager = in_array($user->role, ['admin', 'superadmin'], true) || $user->hasPermission('students.activities');
        $activityTable = $type === 'house' ? 'student_houses' : 'student_clubs';
        $membershipTable = $type === 'house' ? 'student_house_memberships' : 'student_club_memberships';
        $foreignKey = $type === 'house' ? 'student_house_id' : 'student_club_id';
        $group = DB::table($activityTable)->where('school_id', $user->school_id)->where('id', $activity)->first();
        abort_unless($group && ($isManager || (int) $group->patron_user_id === (int) $user->id), 403);

        $members = DB::table($membershipTable.' as membership')
            ->join('students', 'students.id', '=', 'membership.student_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->where('membership.school_id', $user->school_id)->where('membership.'.$foreignKey, $activity)
            ->orderBy('students.name')
            ->get(['students.admission_no','students.name','school_classes.name as class_name','students.gender','students.status']);
        $filename = str($group->name)->slug().'-members-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($members, $group, $type): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, CsvSafe::row([ucfirst($type).' member list', $group->name]));
            fputcsv($output, ['Admission number', 'Student name', 'Class', 'Gender', 'Status']);
            foreach ($members as $member) {
                fputcsv($output, CsvSafe::row([$member->admission_no, $member->name, $member->class_name, ucfirst((string) $member->gender), ucfirst($member->status)]));
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
