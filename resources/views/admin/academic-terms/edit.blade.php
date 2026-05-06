<x-admin.layout :title="__('Edit Academic Term')" :heading="__('Edit Academic Term')" :subheading="__('Update the academic year start, term, or date range for this record.')">
    @include('admin.academic-terms.partials.form', [
        'academicTerm' => $academicTerm,
        'action' => route('admin.academic-terms.update', $academicTerm),
        'method' => 'PUT',
        'submitLabel' => __('Update Academic Term'),
    ])
</x-admin.layout>