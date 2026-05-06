<x-admin.layout :title="__('Add Adviser')" :heading="__('Add Adviser')" :subheading="__('Create an adviser account and department profile.')">
    @include('admin.users.advisers.partials.form', [
        'action' => route('admin.advisers.store'),
        'method' => 'POST',
        'adviser' => null,
        'departments' => $departments,
    ])
</x-admin.layout>