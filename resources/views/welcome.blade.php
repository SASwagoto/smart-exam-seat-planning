<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Exa Seat Planning System | Perfect Allocation</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        smartblue: '#0056A3', // Logo Primary Blue
                        exaorange: '#F27A30', // Logo Orange
                        exagreen: '#6CC04A',   // Logo Green
                        deepblue: '#002C53',  // Darker Blue for Background
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        body { background-color: #002C53; } /* Deep Blue Background to match logo */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .gradient-text-logo {
            background: linear-gradient(90deg, #F27A30, #6CC04A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .text-logo-m {
            color: #595D62; /* Grey color from the M icon and car icons */
        }
        .blob-logo {
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(108, 192, 74, 0.1); /* Green blob */
            filter: blur(70px);
            border-radius: 50%;
            z-index: -1;
        }
    </style>
</head>
<body class="text-white overflow-x-hidden">

    <div class="blob-logo top-[-15%] left-[-10%]"></div>
    <div class="blob-logo bottom-[15%] right-[-5%]" style="background: rgba(242, 122, 48, 0.1);"></div> <nav class="sticky top-0 z-50 bg-black/40 backdrop-blur-md border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-1">
                <img src="{{ asset('frontend/images/logo.png') }}" alt="Smart Exa Logo" class="h-10"> </div>
            <div class="hidden md:flex space-x-8 font-medium text-white/70">
                <a href="#features" class="hover:text-white transition">Features</a>
                <a href="#how-it-works" class="hover:text-white transition">Process</a>
                <a href="#stats" class="hover:text-white transition">Impact</a>
            </div>
            <a href="/admin/login" class="bg-smartblue hover:bg-blue-700 px-6 py-2 rounded-full text-sm font-semibold transition shadow-lg shadow-smartblue/30">
                Admin Portal
            </a>
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
                <button class="bg-exagreen text-black px-10 py-4 rounded-2xl font-bold text-lg hover:bg-white hover:text-black hover:scale-105 transition transform">
                    <i class="fas fa-check-circle mr-2"></i>Automate Seating
                </button>
                <button class="glass-card px-10 py-4 rounded-2xl font-bold text-lg hover:bg-white/10 transition border border-white/20">
                    <i class="fas fa-book mr-2"></i>How It Benefits Us
                </button>
            </div>
        </div>
    </section>

    <section class="px-6 mb-32" data-aos="fade-up" data-aos-duration="1000">
        <div class="max-w-6xl mx-auto glass-card p-6 rounded-[2rem] shadow-2xl flex items-center justify-center">
            <img src="{{ asset('path/to/your/logo.png') }}" alt="Logo Large" class="h-28 opacity-90 transition duration-500 hover:opacity-100"> </div>
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

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
        });
    </script>
</body>
</html>