<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        
        <!-- 🔥 MAIN DOUBLE COLUMN LAYOUT -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- 📌 LEFT COLUMN: Basic Exam Info (4 Columns wide on large screens) -->
            <div class="lg:col-span-4 bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 sticky top-6">
                <div class="border-b border-gray-100 dark:border-gray-800 pb-4 mb-5">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>📝</span> Basic Information
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">পরীক্ষার নাম ও সাধারণ তথ্য নির্বাচন করুন</p>
                </div>

                <div class="space-y-4">
                    <!-- Exam Title -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Exam Title</label>
                        <input type="text" wire:model="title" placeholder="e.g. Mid Term Exam 2026" 
                            class="w-full px-3.5 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        @error('title') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Academic Session -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Academic Session</label>
                        <select wire:model="academic_session_id" class="w-full px-3.5 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                        @error('academic_session_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Department</label>
                        <select wire:model="department_id" class="w-full px-3.5 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Start & End Date -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Start Date</label>
                            <input type="date" wire:model="start_date" class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                            @error('start_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">End Date</label>
                            <input type="date" wire:model="end_date" class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                            @error('end_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <span>💾</span> Save Complete Schedule
                        </button>
                    </div>
                </div>
            </div>

            <!-- 📌 RIGHT COLUMN: Dynamic Schedules & Slots (8 Columns wide) -->
            <div class="lg:col-span-8 space-y-6">
                
                <div class="flex items-center justify-between bg-white dark:bg-gray-900 p-4 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Exam Slots & Routines</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">তারিখ অনুযায়ী পরীক্ষা স্লট ও সাবজেক্ট সিলেক্ট করুন</p>
                    </div>
                    <button type="button" wire:click="addSchedule" class="px-4 py-2 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300 rounded-xl text-xs font-bold hover:bg-indigo-100 transition flex items-center gap-1">
                        <span>➕</span> Add New Slot
                    </button>
                </div>

                <!-- Dynamic Slots Repeater -->
                @foreach($schedules as $sIndex => $sItem)
                    <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-800 relative space-y-4">
                        
                        <!-- Slot Header & Remove Button -->
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-xs font-bold px-3 py-1 bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300 rounded-full">
                                Slot #{{ $sIndex + 1 }}
                            </span>
                            
                            @if(count($schedules) > 1)
                                <button type="button" wire:click="removeSchedule({{ $sIndex }})" class="text-red-500 hover:text-red-700 text-xs font-semibold flex items-center gap-1">
                                    <span>🗑️</span> Remove Slot
                                </button>
                            @endif
                        </div>

                        <!-- Date & Slot Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Exam Date</label>
                                <input type="date" wire:model="schedules.{{ $sIndex }}.date" class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg outline-none">
                                @error("schedules.{$sIndex}.date") <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Time Slot</label>
                                <select wire:model="schedules.{{ $sIndex }}.exam_slot_id" class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg outline-none">
                                    <option value="">Select Time Slot</option>
                                    @foreach($slots as $slot)
                                        <option value="{{ $slot->id }}">{{ $slot->name }}</option>
                                    @endforeach
                                </select>
                                @error("schedules.{$sIndex}.exam_slot_id") <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Rooms Multiple Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Assigned Rooms</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                                @foreach($rooms as $room)
                                    <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" value="{{ $room->id }}" wire:model="schedules.{{ $sIndex }}.selected_rooms" class="rounded text-indigo-600 border-gray-300">
                                        <span>{{ $room->room_number }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Nested Repeater: Batches & Courses -->
                        <div class="pt-2">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Courses in this Slot</label>
                                <button type="button" wire:click="addCourse({{ $sIndex }})" class="text-xs text-indigo-600 font-semibold hover:underline">
                                    + Add Another Course
                                </button>
                            </div>

                            <div class="space-y-2">
                                @foreach($sItem['courses'] as $cIndex => $cItem)
                                    <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800/60 p-2.5 rounded-lg border border-gray-200/60 dark:border-gray-700/60">
                                        
                                        <!-- Batch Select -->
                                        <div class="w-1/2">
                                            <select wire:model="schedules.{{ $sIndex }}.courses.{{ $cIndex }}.batch_id" class="w-full px-2.5 py-1.5 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md">
                                                <option value="">Select Batch</option>
                                                @foreach($batches as $batch)
                                                    <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Course Select -->
                                        <div class="w-1/2">
                                            <select wire:model="schedules.{{ $sIndex }}.courses.{{ $cIndex }}.course_id" class="w-full px-2.5 py-1.5 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md">
                                                <option value="">Select Course</option>
                                                @foreach($courses as $course)
                                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Delete Course Row -->
                                        @if(count($sItem['courses']) > 1)
                                            <button type="button" wire:click="removeCourse({{ $sIndex }}, {{ $cIndex }})" class="text-red-500 hover:text-red-700 p-1">
                                                ✕
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                @endforeach

            </div>

        </div>
    </form>
</div>