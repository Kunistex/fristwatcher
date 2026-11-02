@props(['title' => null])
<!DOCTYPE html>
<html lang="de-CH">
<head>
    <x-head :title="$title" />
</head>
<body class="min-h-dvh">
<main class="mx-auto flex min-h-dvh w-full max-w-[420px] flex-col justify-center px-5 py-12">
    <div class="mb-8 flex items-center gap-3">
        <img src="/icons/icon.svg" alt="" class="size-10">
        <span class="text-[20px] font-semibold tracking-tight">Fristenwächter</span>
    </div>

    @if (session('status'))
        <div class="panel mb-5 border-accent/30 bg-accent-wash px-4 py-3 text-[15px]" role="status">{{ session('status') }}</div>
    @endif

    <div class="panel p-6 sm:p-7">
        {{ $slot }}
    </div>
</main>
</body>
</html>
