
<div class="bg-white border border-gray-200 rounded-xl p-4 space-y-4 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
            <select class="w-full text-sm px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>All Categories</option>
                <option value="15"> aaaaaaa </option>
                <option value="8"> Aerator - Walk Behind </option>
                <option value="7"> Boom Lift </option>
                <option value="14"> booootttt </option>
                <option value="13"> hyhyh both </option>
                <option value="12"> kabba </option>
            </select>
        </div>

        <!-- Search -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search by Content Name</label>
            <input type="text" placeholder="Search by content name..." class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        
    </div>
</div>

<!-- TABLE -->
<div class="overflow-x-auto max-w-full rounded-2xl shadow border border-gray-200 bg-white">

<table class="min-w-full divide-y divide-gray-200 text-sm">

        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Content Cat</th>
                <th class="py-4 px-6 text-left">Content Name</th>
                
                <th class="py-4 px-6 text-left whitespace-nowrap">Subject</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Created</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Send Date</th>

                <th class="py-4 px-6 text-left whitespace-nowrap">Actions</th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200">

<tr class="hover:bg-gray-50 transition">
    <td colspan="6" class="py-6 text-center text-sm text-gray-500">
        No Message found
    </td>
</tr>

    </tbody>

</table>

</div>



@push('js')

<script>

// LIVE CHARACTER COUNTER
document.addEventListener("input", function() {
    const textarea = document.getElementById("contentText");
    if (textarea) {
        document.getElementById("charCount").textContent = textarea.value.length;
    }
});


</script>

@endpush