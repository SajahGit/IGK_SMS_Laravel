<x-layout title="My attendance">
    <x-ui.page-header title="My attendance" :subtitle="$student->section?->full_name" />

    @include('Shared.AttendanceSummary', ['summary' => $summary])
    @include('Shared.AttendanceList', ['records' => $records])
</x-layout>
