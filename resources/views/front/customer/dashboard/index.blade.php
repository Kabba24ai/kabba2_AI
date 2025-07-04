@if(auth('customer')->check())
    <p>Welcome, {{ auth('customer')->user()->full_name }}</p>
    <form method="POST" action="{{ route('front.auth.logout.index') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
@endif
