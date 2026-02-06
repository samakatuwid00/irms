
<div class="max-w-4xl mx-auto py-8">
    <!-- Card: Import Form -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-blue-600 text-white px-6 py-4">
            <h4 class="text-lg font-semibold">Import Print Resources from CSV</h4>
        </div>
        <div class="p-6 space-y-4">
            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                    <button type="button" class="absolute top-2 right-2 text-green-700 hover:text-green-900" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif

            <!-- Error Message -->
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                    <button type="button" class="absolute top-2 right-2 text-red-700 hover:text-red-900" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif

            <!-- Validation Errors -->
            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('import.print-resources') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="csv_file" class="block text-gray-700 font-medium mb-1">CSV File</label>
                    <input type="file" id="csv_file" name="csv_file"
                           accept=".csv,.txt"
                           class="block w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 @error('csv_file') border-red-500 @enderror"
                           required>
                    @error('csv_file')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">Accepted formats: .csv, .txt (Max: 10MB)</p>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 text-sm text-blue-700 rounded">
                    <strong>Import Instructions:</strong>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li>First row should contain headers</li>
                        <li>Expected columns: Title, Type, Author, Publisher, Volume, Copyright Year, Pages, Source, Status, Remarks, Short Name, Quantity, Subject Grade Level IDs</li>
                        <li>Type should be either "Textbook" or "SLR"</li>
                        <li>Subject Grade Level IDs should be in PostgreSQL array format: {uuid1,uuid2,uuid3}</li>
                        <li>All resources will be imported to library: 9ef7447d-1f56-4673-aa5b-eb1465da078b</li>
                        <li>CSV file should be comma-delimited</li>
                    </ul>
                </div>

                <div class="flex space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition">
                        <i class="fas fa-upload mr-2"></i> Import Resources
                    </button>
                    <a href="{{ route('add-resources') }}" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded transition text-center">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Add Resources
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Card: Sample CSV Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mt-6">
        <div class="bg-gray-100 px-6 py-3">
            <h5 class="font-semibold text-gray-700">Sample CSV Format</h5>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Title</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Type</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Author</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Publisher</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Volume</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Copyright Year</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Pages</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Source</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Remarks</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Short Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Quantity</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-600">Subject Grade Level IDs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">Sample Book Title</td>
                        <td class="px-4 py-2">Textbook</td>
                        <td class="px-4 py-2">John Doe</td>
                        <td class="px-4 py-2">ABC Publishing</td>
                        <td class="px-4 py-2">1</td>
                        <td class="px-4 py-2">2024</td>
                        <td class="px-4 py-2">250</td>
                        <td class="px-4 py-2">CO</td>
                        <td class="px-4 py-2">Usable</td>
                        <td class="px-4 py-2">Sample remarks</td>
                        <td class="px-4 py-2">Naga City</td>
                        <td class="px-4 py-2">100</td>
                        <td class="px-4 py-2">{222134a7-7420-490e-ab6c-d2c7df6f7eda,2afbdf77-a659-4b9e-a428-6185f6427077}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
