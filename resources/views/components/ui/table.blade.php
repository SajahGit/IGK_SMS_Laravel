<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <table class="tbl">
        @isset($head)
            <thead><tr>{{ $head }}</tr></thead>
        @endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
