<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Exa Seat Planning System | Find Your Seat</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        smartblue: '#0056A3',
                        exaorange: '#F27A30',
                        exagreen: '#6CC04A',
                        deepblue: '#002C53',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #002C53; font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); }
        select option { background-color: #002C53; color: white; }
        .gradient-text-logo { background: linear-gradient(90deg, #F27A30, #6CC04A); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .blob-logo { position: absolute; width: 400px; height: 400px; background: rgba(108, 192, 74, 0.1); filter: blur(70px); border-radius: 50%; z-index: -1; }
    </style>
</head>

<body class="text-white overflow-x-hidden" x-data="seatSearch()">

    <div class="blob-logo top-[-15%] left-[-10%]"></div>
    <div class="blob-logo bottom-[15%] right-[-5%]" style="background: rgba(242, 122, 48, 0.1);"></div>

    <nav class="sticky top-0 z-50 bg-black/40 backdrop-blur-md border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-1">
                <img src="{{ asset('frontend/images/logo.png') }}" alt="Smart Exa Logo" class="h-10">
            </div>
            <a href="/admin/login" class="bg-smartblue hover:bg-blue-700 px-6 py-2 rounded-full text-sm font-semibold transition">Admin Portal</a>
        </div>
    </nav>

    <section class="relative pt-20 pb-32 px-6">
        <div class="max-w-7xl mx-auto text-center" data-aos="zoom-out" data-aos-duration="1200">
            <span class="px-4 py-2 rounded-full bg-exagreen/10 text-exagreen text-xs font-bold uppercase tracking-widest border border-exagreen/20">
                Modernizing Campus Exam Planning
            </span>
            <h1 class="mt-8 text-5xl md:text-8xl font-black leading-tight text-white">
                Achieve Smart <span class="gradient-text-logo">Exa</span><span class="text-logo-m">m</span>
                <br> <span class="text-exaorange">Allocations</span> Without Stress
            </h1>
            <p class="mt-8 text-white/80 text-lg md:text-xl max-w-3xl mx-auto leading-relaxed">
                Streamline seat arrangements, eliminate conflicts, and generate instant reports. Our automated system handles the complexity, giving you time for strategic planning.
            </p>
            <div class="mt-12 flex flex-col sm:flex-row justify-center gap-6">
                <button @click="openModal = true" class="mt-10 bg-exagreen text-black px-10 py-4 rounded-2xl font-bold text-lg hover:scale-105 transition shadow-xl shadow-exagreen/20">
                <i class="fas fa-search mr-2"></i>Search Your Seat
            </button>
                <button class="mt-10 bg-smartblue text-white px-10 py-4 rounded-2xl font-bold text-lg hover:scale-105 transition shadow-xl shadow-smartblue/20">
                    <i class="fas fa-book mr-2"></i>How It Benefits Us
                </button>
            </div>
        </div>
    </section>

    <section class="px-6 mb-32" data-aos="fade-up" data-aos-duration="1000">
        <div class="max-w-6xl mx-auto glass-card p-6 rounded-[2rem] shadow-2xl flex items-center justify-center">
            <img src="{{ asset('frontend/images/logo.png') }}" alt="Logo Large" class="h-28 opacity-90 transition duration-500 hover:opacity-100"> </div>
    </section>

    <section id="features" class="py-24 px-6 relative">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-4xl md:text-5xl font-bold text-white">Perfect Seating, Perfect Integrity</h2>
                <p class="text-white/60 mt-4 max-w-2xl mx-auto">Designed to improve administrative workflow and ensure academic integrity.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="glass-card p-10 rounded-3xl group hover:border-exagreen/50 transition duration-500" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-16 h-16 bg-exagreen/10 rounded-2xl flex items-center justify-center text-exagreen text-3xl mb-8 group-hover:scale-110 transition">
                        <i class="fas fa-chess-board"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-exagreen">Rule-Based Mixing</h3>
                    <p class="text-white/70 leading-relaxed">System mixes students from different departments or courses based on rules to reduce opportunity for collusion.</p>
                </div>

                <div class="glass-card p-10 rounded-3xl group hover:border-exaorange/50 transition duration-500" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-16 h-16 bg-exaorange/10 rounded-2xl flex items-center justify-center text-exaorange text-3xl mb-8 group-hover:scale-110 transition">
                        <i class="fas fa-print"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-exaorange">One-Click Printables</h3>
                    <p class="text-white/70 leading-relaxed">Instant PDF generation for room seating lists, seat labels, invigilator duty charts, and more. No manual effort.</p>
                </div>

                <div class="glass-card p-10 rounded-3xl group hover:border-smartblue/50 transition duration-500" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-16 h-16 bg-smartblue/10 rounded-2xl flex items-center justify-center text-smartblue text-3xl mb-8 group-hover:scale-110 transition">
                        <i class="fas fa-sync"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-smartblue">Dynamic Re-Calibrating</h3>
                    <p class="text-white/70 leading-relaxed">Real-time adjustments for room changes, student withdrawal, or rescheduling without disrupting the entire plan.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-24 bg-white/5">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-20" data-aos="fade-down">
                <h2 class="text-4xl font-bold text-white">The Smart Exa Process</h2>
                <p class="text-white/60 mt-4 max-w-2xl mx-auto">From data entry to final seat plan, Smart Exa makes it a breeze.</p>
            </div>
            
            <div class="space-y-16">
                <div class="flex flex-col md:flex-row items-center gap-12" data-aos="fade-right">
                    <div class="w-24 h-24 bg-smartblue rounded-full flex items-center justify-center text-4xl font-black shrink-0 shadow-xl shadow-smartblue/40">01</div>
                    <div class="glass-card p-8 rounded-2xl flex-grow">
                        <h4 class="text-2xl font-bold mb-3 text-white">Import Campus Data</h4>
                        <p class="text-white/70">Securely upload CSV/Excel files of student enrollments, course lists, invigilators, and room/hall capacities. Smart validation ensures data accuracy.</p>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row-reverse items-center gap-12 text-right md:text-left" data-aos="fade-left">
                    <div class="w-24 h-24 bg-exaorange rounded-full flex items-center justify-center text-4xl font-black shrink-0 shadow-xl shadow-exaorange/40">02</div>
                    <div class="glass-card p-8 rounded-2xl flex-grow">
                        <h4 class="text-2xl font-bold mb-3 text-white">Define Logic & Schedule</h4>
                        <p class="text-white/70">Define exam dates, shifts, and seating preferences (e.g., zig-zag pattern, single-course mixing). Schedule exams with conflict checking.</p>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-12" data-aos="fade-right">
                    <div class="w-24 h-24 bg-exagreen rounded-full flex items-center justify-center text-4xl font-black shrink-0 shadow-xl shadow-exagreen/40">03</div>
                    <div class="glass-card p-8 rounded-2xl flex-grow">
                        <h4 class="text-2xl font-bold mb-3 text-white">Generate & Export Seating</h4>
                        <p class="text-white/70">Run the automated allocator. Instantly review the seat plan, generate all necessary PDF documents, and publish results with confidence.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-20 px-6 border-t border-white/5 text-center">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl font-bold mb-8 text-white">Ready for error-free exams?</h2>
            <button class="bg-exaorange px-12 py-4 rounded-2xl font-bold hover:bg-white hover:text-black hover:scale-105 transition mb-12 transform">
                <i class="fas fa-play mr-2"></i>Launch Smart Exa
            </button>
            <p class="text-white/50 text-sm">
                &copy; 2026 Smart Exa Seat Planning System. All rights reserved. <br>
                Crafted by <strong>Shawon Ahmed Swagoto</strong>
            </p>
        </div>
    </footer>

    <!-- Modal -->
    <div x-show="openModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" x-cloak x-transition>
        <div class="glass-card w-full max-w-xl rounded-3xl p-6 border border-white/20 relative" @click.away="resetModal()">
            <button @click="resetModal()" class="absolute top-4 right-4 text-white/50 hover:text-white text-3xl">&times;</button>
            
            <h2 class="text-2xl font-bold mb-6 gradient-text-logo">Exam Seat Finder</h2>

            <!-- Search Form -->
            <div class="space-y-4" x-show="!resultData">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <select x-model="semesterId" @change="fetchExams()" class="bg-white/5 border border-white/10 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-exagreen appearance-none text-sm">
                        <option value="">Semester</option>
                        @foreach($semesters as $sem) <option value="{{ $sem->id }}">{{ $sem->name }}</option> @endforeach
                    </select>
                    <select x-model="deptId" @change="fetchExams()" class="bg-white/5 border border-white/10 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-exagreen appearance-none text-sm">
                        <option value="">Department</option>
                        @foreach($departments as $dept) <option value="{{ $dept->id }}">{{ $dept->name }}</option> @endforeach
                    </select>
                </div>
                <select x-model="examId" :disabled="!exams.length" class="w-full bg-white/5 border border-white/10 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-exaorange disabled:opacity-30 appearance-none text-sm">
                    <option value="">Choose Exam</option>
                    <template x-for="exam in exams" :key="exam.id">
                        <option :value="exam.id" x-text="exam.name"></option>
                    </template>
                </select>
                <input type="text" x-model="studentId" placeholder="Enter Student ID" class="w-full bg-white/5 border border-white/10 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-smartblue text-sm">
                
                <button @click="search()" :disabled="loading" class="w-full bg-smartblue py-3.5 rounded-xl font-bold hover:bg-blue-600 transition flex justify-center items-center shadow-lg">
                    <span x-show="!loading">Search Now</span>
                    <span x-show="loading" class="animate-spin border-2 border-white/30 border-t-white rounded-full h-5 w-5"></span>
                </button>
                <p x-show="errorMessage" x-text="errorMessage" class="text-red-400 text-xs text-center"></p>
            </div>

            <!-- Result View (Optimized for Image) -->
            <div x-show="resultData" x-transition>
                <div id="capture-area" class="bg-deepblue p-4 rounded-2xl border border-white/20 shadow-2xl">
                    <!-- Header -->
                    <div class="flex justify-between items-center border-b border-white/10 pb-3 mb-3">
                        <div>
                            <h3 class="text-lg font-black text-white leading-none uppercase" x-text="resultData.student_name"></h3>
                            <p class="text-exagreen font-bold text-[10px] mt-1 tracking-widest">ID: <span class="text-white" x-text="resultData.student_id"></span></p>
                        </div>
                        <img src="{{ asset('frontend/images/logo.png') }}" class="h-6">
                    </div>

                    <!-- Seat List -->
                    <div class="space-y-2">
                        <template x-for="seat in resultData.seats" :key="seat.id">
                            <div class="bg-white/5 px-3 py-2.5 rounded-lg border-l-4 border-exaorange flex justify-between items-center">
                                <div class="flex-1">
                                    <p class="font-bold text-white text-[16px] leading-tight mb-1" x-text="seat.session_course.course.course_title"></p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="text-[12px] bg-white/10 text-white/80 px-1.5 py-0.5 rounded uppercase" x-text="formatDate(seat.session_course.session.exam_date)"></span>
                                        <!-- Time Range Format -->
                                        <span class="text-[12px] bg-smartblue/20 text-exaorange px-1.5 py-0.5 rounded  uppercase">
                                            <i class="far fa-clock mr-1"></i>
                                            <span x-text="formatTime(seat.session_course.session.exam_slot.start_time) + ' - ' + formatTime(seat.session_course.session.exam_slot.end_time)"></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right ml-4 pl-3 border-l border-white/10">
                                    <div class="text-[8px] text-white/40 font-bold uppercase tracking-widest leading-none">Room</div>
                                    <div class="text-[16px] font-black text-white leading-tight" x-text="seat.room.room.room_number"></div>
                                    <div class="mt-0.5 inline-block bg-exagreen text-deepblue px-2 py-0.5 rounded text-[12px] font-black uppercase tracking-tighter" x-text="'Seat: ' + seat.seat_label"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <div class="mt-4 text-[8px] text-white/20 flex justify-between items-center border-t border-white/5 pt-2">
                        <span>Smart Exa Seating System</span>
                        <span class="text-exaorange font-black uppercase tracking-widest">Confidential Plan</span>
                    </div>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <button @click="downloadImage()" class="flex-1 bg-exagreen text-black py-3 rounded-xl font-bold text-sm shadow-lg hover:scale-[1.02] transition">
                        <i class="fas fa-file-image mr-2"></i>Download Image
                    </button>
                    <button @click="resultData = null" class="flex-1 bg-white/10 py-3 rounded-xl font-bold text-sm hover:bg-white/20 transition">
                        Search Again
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });

        function seatSearch() {
            return {
                openModal: false, loading: false, semesterId: '', deptId: '', examId: '', studentId: '', exams: [], resultData: null, errorMessage: '',

                fetchExams() {
                    if (!this.semesterId || !this.deptId) { this.exams = []; return; }
                    fetch(`/get-exams-filtered?semester_id=${this.semesterId}&dept_id=${this.deptId}`).then(res => res.json()).then(data => this.exams = data);
                },

                formatDate(dateString) {
                    if(!dateString) return "";
                    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                },

                // ১২ ঘণ্টার ফরম্যাটে টাইম কনভার্ট করার ফাংশন
                formatTime(timeString) {
                    if(!timeString) return "";
                    // timeString format: "14:00:00"
                    let [hours, minutes] = timeString.split(':');
                    let h = parseInt(hours);
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    h = h % 12 || 12; // ০ কে ১২ এ রূপান্তর
                    return `${h}:${minutes} ${ampm}`;
                },

                search() {
                    if(!this.examId || !this.studentId) { this.errorMessage = "Missing Required Info."; return; }
                    this.loading = true; this.errorMessage = '';
                    fetch('/find-seat', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ exam_id: this.examId, student_id: this.studentId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if(data.error) this.errorMessage = data.error;
                        else this.resultData = data;
                    }).catch(() => { this.loading = false; this.errorMessage = "Error connecting to server."; });
                },

                downloadImage() {
                    const element = document.getElementById('capture-area');
                    html2canvas(element, {
                        backgroundColor: '#002C53',
                        scale: 3, 
                        logging: false,
                        useCORS: true
                    }).then(canvas => {
                        const link = document.createElement('a');
                        link.download = `SeatPlan-${this.studentId}.png`;
                        link.href = canvas.toDataURL("image/png");
                        link.click();
                    });
                },

                resetModal() { this.openModal = false; this.resultData = null; this.errorMessage = ''; this.exams = []; }
            }
        }
    </script>
</body>
</html>