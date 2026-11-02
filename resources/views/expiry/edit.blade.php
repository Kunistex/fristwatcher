<x-layouts.app :title="$item->name" :back="route('expiry.index')">
    <h1 class="text-[28px] font-semibold tracking-tight">{{ $item->name }} bearbeiten</h1>
    <form method="POST" action="{{ route('expiry.update', $item) }}" class="panel mt-6 max-w-[520px] space-y-4 p-5 sm:p-6">
        @csrf @method('PUT')
        @include('expiry._fields', ['item' => $item])
        <div class="flex flex-wrap gap-3 pt-2">
            <button class="btn-primary">Änderungen speichern</button>
            <a href="{{ route('expiry.index') }}" class="btn-quiet">Abbrechen</a>
        </div>
    </form>
    <form method="POST" action="{{ route('expiry.destroy', $item) }}" class="mt-4 max-w-[520px]" onsubmit="return confirm('Eintrag löschen?')">
        @csrf @method('DELETE')
        <button class="btn-danger"><x-icon name="trash" class="size-4" /> Löschen</button>
    </form>
</x-layouts.app>
