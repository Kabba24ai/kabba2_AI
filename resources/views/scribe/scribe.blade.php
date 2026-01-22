<script>
window.addEventListener("DOMContentLoaded", () => {
    // Listen for login response and store token
    document.addEventListener("apiResponse", (event) => {
        if (event.detail.request.url.includes("/login")) {
            const token = event.detail.response.body.token;
            if (token) {
                localStorage.setItem("auth_token", token);
                window.useToken(token);
            }
        }
    });

    // Helper: attach token automatically
    window.useToken = function(token) {
        localStorage.setItem("auth_token", token);
        window.addEventListener("beforeApiRequest", (event) => {
            event.detail.request.headers["Authorization"] = "Bearer " + token;
        });
    };

    // Restore token if already stored
    const stored = localStorage.getItem("auth_token");
    if (stored) {
        window.useToken(stored);
    }
});
</script>
