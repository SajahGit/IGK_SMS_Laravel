@props(['action', 'message' => 'Are you sure?', 'label' => 'Delete', 'method' => 'DELETE'])
<form method="POST" action="{{ $action }}" class="inline"
      onsubmit="return confirm('{{ addslashes($message) }}')">
    @csrf
    @method($method)
    <button type="submit" class="btn btn-danger btn-sm">{{ $label }}</button>
</form>
