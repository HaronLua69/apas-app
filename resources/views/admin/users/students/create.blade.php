<x-admin.layout :title="__('Add Student')" :heading="__('Add Student')" :subheading="__('Create a student account, profile, and active admission record.')">
    @include('admin.users.students.partials.form', [
        'action' => route('admin.students.store'),
        'method' => 'POST',
        'student' => null,
        'courses' => $courses,
        'majors' => $majors,
    ])
</x-admin.layout>