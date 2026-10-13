{{-- needs: $records = StudentReportService::attendanceList() (paginator) --}}
<x-ui.card title="Daily attendance">
    @if ($records->isEmpty())
        <x-ui.empty-state message="No daily records yet." />
    @else
        <x-ui.table>
            <x-slot:head><th>Date</th><th>Subject</th><th>Status</th><th>Remarks</th></x-slot:head>
            @foreach ($records as $r)
                @php
                    $color = ['present' => 'green', 'late' => 'amber', 'absent' => 'red', 'excused' => 'blue'][$r->status] ?? 'slate';
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->session_date)->format('D, d M Y') }}</td>
                    <td>{{ $r->subject }}</td>
                    <td><x-ui.badge :color="$color">{{ ucfirst($r->status) }}</x-ui.badge></td>
                    <td>{{ $r->remarks ?? '-' }}</td>
                </tr>
            @endforeach
        </x-ui.table>
        <div class="mt-4">{{ $records->links() }}</div>
    @endif
</x-ui.card>
