{{-- needs: $results = StudentReportService::results() --}}
@if ($results->isEmpty())
    <x-ui.empty-state message="No results have been published yet." />
@else
    @foreach ($results as $item)
        @php $res = $item['result']; @endphp
        <x-ui.card class="mb-5">
            <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-lg font-bold text-slate-900">{{ $item['exam']->name }}</div>
                    <div class="text-sm text-slate-500">
                        {{ ucfirst($item['exam']->type) }} &middot; {{ $item['exam']->term?->full_name }}
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-ui.badge color="blue">GPA {{ number_format($res->gpa, 2) }}</x-ui.badge>
                    <x-ui.badge :color="$res->subjects_passed === $res->subjects_total ? 'green' : 'amber'">
                        Passed {{ $res->subjects_passed }} / {{ $res->subjects_total }}
                    </x-ui.badge>
                </div>
            </div>

            <x-ui.table>
                <x-slot:head><th>Subject</th><th>Marks</th><th>Percentage</th><th>Grade</th><th>Result</th></x-slot:head>
                @foreach ($item['subjects'] as $sr)
                    <tr>
                        <td>{{ $sr->classSubject?->subject?->name }}</td>
                        <td>{{ $sr->is_absent ? '-' : rtrim(rtrim(number_format($sr->marks_obtained, 2, '.', ''), '0'), '.') }} / {{ $sr->max_marks }}</td>
                        <td>{{ $sr->is_absent ? '-' : $sr->percentage.'%' }}</td>
                        <td><strong>{{ $sr->grade }}</strong></td>
                        <td>
                            @if ($sr->is_absent) <x-ui.badge color="amber">Absent</x-ui.badge>
                            @elseif ($sr->is_pass) <x-ui.badge color="green">Pass</x-ui.badge>
                            @else <x-ui.badge color="red">Fail</x-ui.badge> @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>
    @endforeach
@endif
