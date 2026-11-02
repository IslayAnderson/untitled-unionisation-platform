<x-layouts.app title="Confirm your email">
    <h1 class="mb-2 text-2xl font-semibold">Confirm your email</h1>
    <p class="mb-6 text-stone-600">
        We've sent a link to {{ auth()->user()->email }}. Click it to continue setting up your union membership.
    </p>

    @if (session('status') === 'verification-link-sent')
        <p class="mb-4 text-sm text-emerald-800">A new confirmation link has been sent.</p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-button>Resend confirmation email</x-button>
    </form>
</x-layouts.app>
