<x-admin.layout :title="__('Edit Student')" :heading="__('Edit Student')" :subheading="__('Update the student account, profile, and active admission record.')">
    @include('admin.users.students.partials.form', [
        'action' => route('admin.students.update', $student),
        'method' => 'PUT',
        'student' => $student,
        'courses' => $courses,
        'majors' => $majors,
    ])
</x-admin.layout>