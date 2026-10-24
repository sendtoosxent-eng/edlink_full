#if DEBUG
import SwiftUI

// Screenshot-only fixtures. This never creates or persists an authenticated session.
struct DesignPreview: View {
    let role: String
    @StateObject private var session = Session()
    static var requestedRole: String? {
        guard let argument = ProcessInfo.processInfo.arguments.first(where: { $0.hasPrefix("--edlink-design-role=") }), let value = argument.split(separator: "=").last.map(String.init), ["teacher", "student", "parent"].contains(value) else { return nil }
        return value
    }
    var body: some View {
        MainTabs(previewData: fixture).environmentObject(session).preferredColorScheme(.light)
            .task {
                session.user = .object(["id": .number(1), "name": .string(role == "teacher" ? "Grace Namusoke" : role == "parent" ? "Sarah Kato" : "Daniel Kato"), "role": .string(role), "email": .string("preview@example.test"), "school": .object(["name": .string("Edlink Demonstration School"), "number": .string("EDL-PREVIEW")])])
                session.children = [fixture["student"]]; session.studentID = "1"
            }
    }
    private var fixture: JSON {
        let source = #"{"date":"2026-10-24","current_term":{"name":"Term 3","is_open":true},"student":{"id":1,"name":"Daniel Kato","class":"Senior 2","stream":"East"},"attendance":{"present":34,"late":2,"absent":1},"next_lesson":{"id":1,"subject":"Mathematics","class_name":"Senior 2","starts_at":"10:00:00","ends_at":"11:00:00"},"today_timetable":[{"id":1,"subject":"Mathematics","class_name":"Senior 2","starts_at":"10:00:00","ends_at":"11:00:00"},{"id":2,"subject":"English","class_name":"Senior 2","starts_at":"11:30:00","ends_at":"12:30:00"}],"homework":[{"id":1,"title":"Quadratic equations","subject":{"name":"Mathematics"},"due_at":"2026-10-28"}],"events":[{"id":1,"title":"Parents’ visiting day","event_date":"2026-11-01"}],"analytics":{"stats":{"learners":124,"classes":4,"subjects":3,"lessons_today":2,"attendance_today":96,"pending_marks":1,"homework":3,"published_results":2},"attendance_labels":["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],"present_series":[28,31,30,29,32,12,0],"absent_series":[2,1,2,1,0,1,0],"performance_labels":["Mathematics","English","Biology"],"performance_series":[78,84,72]},"teacher_workspace":{"role_label":"Class teacher","term":"Term 3","class_teacher_classes":[{"id":1,"name":"Senior 2 East"}],"subject_assignments":[{"school_class_id":1,"class_name":"Senior 2","subject_id":1,"subject_name":"Mathematics"}],"pending_attendance":[{"school_class_id":1,"attendance_type":"daily","label":"Senior 2 East","remaining":12}],"pending_results":[{"paper_id":1,"exam_name":"Midterm","class_name":"Senior 2","subject_name":"Mathematics"}],"tools":[{"id":"students.index","group":"My classes"},{"id":"exams.marks","group":"Teaching"},{"id":"attendance.reports","group":"Reports"},{"id":"leaves.index","group":"My school"}]}}"#
        return (try? JSONDecoder().decode(JSON.self, from: Data(source.utf8))) ?? .null
    }
}
#endif
