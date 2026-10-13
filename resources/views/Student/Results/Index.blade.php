<x-layout title="My results">
    <x-ui.page-header title="My results" subtitle="Only published results are shown here." />

    @include('Shared.Results', ['results' => $results])
</x-layout>
