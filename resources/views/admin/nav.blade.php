<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold">{{ $title }}</h1>
    <nav class="flex gap-4 text-sm">
        <a href="{{ route('admin.unions.index') }}" @class(['hover:underline', 'font-semibold' => request()->routeIs('admin.unions.*')])>Unions</a>
        <a href="{{ route('admin.cleanup.index') }}" @class(['hover:underline', 'font-semibold' => request()->routeIs('admin.cleanup.*')])>Clean up</a>
    </nav>
</div>
