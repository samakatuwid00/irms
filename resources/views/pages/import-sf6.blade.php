@extends('pages.layout.layout')

@section('title', 'Import SF6')
@section('page-title', 'Import SF6')

@section('header-title', 'Import SF6 Data')
@section('header-subtitle', 'Upload an SF6 Excel file to auto-fill student population')
@section('breadcrumb', 'Import SF6')

@section('content')

    @include('pages.partials.page-header')

    {{-- Flash Messages --}}
    @if (session('success'))
        <div id="alertBox" class="mb-4 rounded-lg px-4 py-3 relative bg-green-100 text-green-800">
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.classList.add('hidden')"
                class="absolute top-1 right-1 text-gray-500 hover:text-gray-800">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-100 text-red-800 px-4 py-3">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="p-6 space-y-6">

        {{-- Back link --}}
        <div>
            <a href="{{ route('school-profile') }}"
               class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 hover:underline transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 19l-7-7 7-7"/>
                </svg>
                Back to School Profile
            </a>
        </div>

        {{-- ==================== UPLOAD CARD ==================== --}}
        <div class="bg-white rounded-xl shadow p-6 max-w-2xl">

            <h3 class="text-lg font-semibold mb-1">Upload SF6 Excel File</h3>
            <p class="text-sm text-gray-500 mb-6">
                Accepted format: <strong>.xls</strong> or <strong>.xlsx</strong> — DepEd School Form 6 (SF6).
                The system will dynamically detect all grade columns (Kindergarten – Grade 12)
                from the file and map the Male / Female counts for whichever grade levels are present.
            </p>

            <form id="sf6Form"
                  action="{{ route('import.sf6.preview') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                {{-- School Year --}}
                <div class="mb-5">
                    <label class="text-sm font-medium text-gray-700 mb-1 block">
                        School Year <span class="text-red-500">*</span>
                    </label>
                    <select name="sy_id" required
                        class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg
                               focus:border-blue-500 focus:outline-none transition">
                        <option value="">-- Select School Year --</option>
                        @foreach ($schoolYears as $sy)
                            <option value="{{ $sy->id }}">
                                {{ $sy->year_start }} – {{ $sy->year_end }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- File Input --}}
                <div class="mb-6">
                    <label class="text-sm font-medium text-gray-700 mb-1 block">
                        SF6 File <span class="text-red-500">*</span>
                    </label>

                    <label for="sf6_file"
                        class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed
                               border-gray-300 rounded-xl cursor-pointer bg-gray-50
                               hover:border-blue-400 hover:bg-blue-50 transition"
                        id="dropZone">
                        <div class="flex flex-col items-center gap-2 pointer-events-none" id="dropLabel">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-400"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                            </svg>
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold text-blue-600">Click to upload</span>
                                or drag and drop
                            </p>
                            <p class="text-xs text-gray-400">XLS or XLSX (max 5 MB)</p>
                        </div>
                        <input type="file" id="sf6_file" name="sf6_file"
                               accept=".xls,.xlsx" class="hidden">
                    </label>

                    {{-- File name preview --}}
                    <p id="fileName" class="mt-2 text-sm text-gray-600 hidden">
                        📄 <span id="fileNameText"></span>
                    </p>
                </div>

                <button type="submit" id="previewBtn"
                    class="w-full py-2.5 rounded-lg bg-blue-600 text-white font-medium
                           hover:bg-blue-700 shadow transition">
                    Preview Extracted Data
                </button>
            </form>
        </div>

        {{-- ==================== PREVIEW TABLE ==================== --}}
        <div id="previewSection" class="hidden bg-white rounded-xl shadow p-6 max-w-2xl">

            <h3 class="text-lg font-semibold mb-1">Preview</h3>
            <p class="text-sm text-gray-500 mb-4">
                Review the extracted population data below. Only grades offered by your school will be saved.
            </p>

            <div class="overflow-x-auto mb-6">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600">
                            <th class="px-4 py-2 text-left font-semibold">Grade Level</th>
                            <th class="px-4 py-2 text-center font-semibold">Male</th>
                            <th class="px-4 py-2 text-center font-semibold">Female</th>
                            <th class="px-4 py-2 text-center font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody id="previewTableBody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>

            {{-- Hidden form to submit the actual file for saving --}}
            <form id="confirmForm"
                  action="{{ route('import.sf6.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="sy_id" id="hiddenSyId">
                {{-- File is re-attached via JS before submit --}}
                <input type="file" name="sf6_file" id="hiddenFile" class="hidden">

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="resetForm()"
                        class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700
                               hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 rounded-lg bg-green-600 text-white font-medium
                               hover:bg-green-700 shadow transition">
                        ✓ Confirm &amp; Save
                    </button>
                </div>
            </form>
        </div>

    </div>

    @vite('resources/js/import-sf6.js')
@endsection
