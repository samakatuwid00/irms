@extends('pages.layout.layout')

@section('title', 'School Profile')

@section('page-title', 'School Profile')

@section('content')
        <div class="p-6 space-y-6">

            <!-- ================= SCHOOL PROFILE ================= -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">School Profile</h2>

                    <div class="flex gap-2">
                        <button id="editProfileBtn"
                                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Edit
                        </button>

                        <button id="saveProfileBtn"
                                class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 hidden">
                            Save
                        </button>

                        <button id="cancelProfileBtn"
                                class="px-4 py-2 text-sm bg-gray-500 text-white rounded-lg hover:bg-gray-600 hidden">
                            Cancel
                        </button>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-6">

                    <!-- Logo -->
                    <div class="flex flex-col items-center gap-3 w-full md:w-1/4">
                        <img id="schoolLogoPreview"
                            src="{{  asset('assets/images/default.jpg') }}"
                            alt="School Logo"
                            class="w-32 h-32 object-contain border rounded-lg">

                        <input type="file" id="schoolLogoInput"
                            class="hidden" accept="image/*">

                        <button id="changeLogoBtn"
                                class="text-sm text-blue-600 hover:underline hidden">
                            Change Logo
                        </button>
                    </div>

                    <!-- School Info -->
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="text-xs text-gray-500">School Name</label>
                            <input type="text" value="San Francisco National High School"
                                class="profile-input w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100"
                                disabled>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500">School ID</label>
                            <input type="text" value="305432"
                                class="profile-input w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100"
                                disabled>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500">District</label>
                            <input type="text" value="Camarines Sur"
                                class="profile-input w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100"
                                disabled>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500">Principal</label>
                            <input type="text" value="Juan Dela Cruz"
                                class="profile-input w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100"
                                disabled>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ================= SCHOOL YEAR CONTROLS ================= -->
            <div class="bg-white rounded-xl shadow p-4 flex flex-col md:flex-row gap-4 md:items-end">

                <!-- Filter School Year -->
                <div>
                    <label class="text-xs text-gray-500">School Year</label>
                    <select class="mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option>2024 – 2025</option>
                        <option>2023 – 2024</option>
                        <option>2022 – 2023</option>
                    </select>
                </div>

                <!-- Add School Year Dropdown -->
                <div>
                    <label class="text-xs text-gray-500">Add School Year</label>
                    <select class="mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option>2025 – 2026</option>
                        <option>2024 – 2025</option>
                        <option>2023 – 2024</option>
                    </select>
                </div>

                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Add
                </button>
            </div>

            <!-- ================= POPULATION TABLE ================= -->
            <div class="bg-white rounded-xl shadow overflow-hidden">

                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Grade Level</th>
                            <th class="px-4 py-3 text-center">Male</th>
                            <th class="px-4 py-3 text-center">Female</th>
                            <th class="px-4 py-3 text-center">Total</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">

                        @php
                            $grades = ['Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'];
                        @endphp

                        @foreach ($grades as $grade)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $grade }}</td>

                            <td class="px-4 py-3 text-center">
                                <input type="number" value="0"
                                    class="male w-20 text-center border rounded-lg px-2 py-1">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input type="number" value="0"
                                    class="female w-20 text-center border rounded-lg px-2 py-1">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input type="number" value="0" readonly
                                    class="total w-20 text-center bg-gray-100 border rounded-lg px-2 py-1">
                            </td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>

            <!-- ================= SAVE POPULATION ================= -->
            <div class="flex justify-end">
                <button class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Save Population
                </button>
            </div>

        </div>

        <!-- ================= SCRIPTS ================= -->
        <script>
            /* PROFILE EDIT TOGGLE */
            const editBtn = document.getElementById('editProfileBtn');
            const saveBtn = document.getElementById('saveProfileBtn');
            const cancelBtn = document.getElementById('cancelProfileBtn');
            const inputs = document.querySelectorAll('.profile-input');
            const changeLogoBtn = document.getElementById('changeLogoBtn');
            const logoInput = document.getElementById('schoolLogoInput');
            const logoPreview = document.getElementById('schoolLogoPreview');

            editBtn.onclick = () => {
                inputs.forEach(i => {
                    i.disabled = false;
                    i.classList.remove('bg-gray-100');
                });
                editBtn.classList.add('hidden');
                saveBtn.classList.remove('hidden');
                cancelBtn.classList.remove('hidden');
                changeLogoBtn.classList.remove('hidden');
            };

            cancelBtn.onclick = () => location.reload();

            /* LOGO PREVIEW */
            changeLogoBtn.onclick = () => logoInput.click();
            logoInput.onchange = e => {
                const file = e.target.files[0];
                if (file) logoPreview.src = URL.createObjectURL(file);
            };

            /* POPULATION AUTO TOTAL */
            document.querySelectorAll('tbody tr').forEach(row => {
                const male = row.querySelector('.male');
                const female = row.querySelector('.female');
                const total = row.querySelector('.total');

                function updateTotal() {
                    total.value = (parseInt(male.value) || 0) + (parseInt(female.value) || 0);
                }

                male.addEventListener('input', updateTotal);
                female.addEventListener('input', updateTotal);
            });
        </script>
@endsection
