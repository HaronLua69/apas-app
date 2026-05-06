<x-admin.layout :title="__('Edit Adviser')" :heading="__('Edit Adviser')" :subheading="__('Update the adviser account and department profile.')">
    @include('admin.users.advisers.partials.form', [
        'action' => route('admin.advisers.update', $adviser),
        'method' => 'PUT',
        'adviser' => $adviser,
        'departments' => $departments,
    ])
</x-admin.layout>