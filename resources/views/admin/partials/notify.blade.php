@if (session('success'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            notyf.success("{{ session('success') }}");
        });
    </script>
@endif

@if (session('error'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            notyf.error("{{ session('error') }}");
        });
    </script>
@endif

@if ($errors->any())
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @foreach ($errors->all() as $error)
                notyf.error("{{ $error }}");
            @endforeach
        });
    </script>
@endif
