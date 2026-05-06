<x-admin.layout :title="__('Add Academic Term')" :heading="__('Add Academic Term')" :subheading="__('Set the academic year start, term, and date range so the current term can be resolved automatically.')">
    @include('admin.academic-terms.partials.form', [
        'academicTerm' => null,
        'action' => route('admin.academic-terms.store'),
        'method' => 'POST',
        'submitLabel' => __('Save Academic Term'),
    ])
</x-admin.layout>