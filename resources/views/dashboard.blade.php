<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAMBAK - Monitoring Kepiting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">

    <div class="w-full min-h-screen p-4 md:p-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div class="flex-1">
                <h1 class="text-3xl font-extrabold text-blue-900">Home</h1>
                <p class="text-base text-gray-700 font-medium">TAMBAK (Tingkat Akurasi Monitoring Budidaya Kepiting)</p>
                <p class="text-sm text-gray-500 italic">Sistem monitoring kualitas air tambak kepiting bakau menggunakan metode Fuzzy Tsukamoto</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button class="bg-white px-5 py-2.5 rounded-lg shadow-sm text-blue-600 font-bold border-b-4 border-blue-600 transition-all active:transform active:scale-95">
                    <i class="fas fa-home mr-2"></i>Home
                </button>
                <a href="{{ route('fuzzy.rules') }}" class="bg-white px-5 py-2.5 rounded-lg shadow-sm text-gray-600 font-medium hover:bg-gray-50 transition-all active:transform active:scale-95 flex items-center">
                    <i class="fas fa-check-square mr-2 text-blue-500"></i>Fuzzy Rules
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-4 bg-white p-8 rounded-2xl shadow-sm border-t-8 border-blue-600 flex flex-col justify-between">
                <div>
                    <h3 class="text-gray-500 font-bold uppercase tracking-wider text-sm mb-6 flex items-center">
                        <span class="w-2 h-2 bg-blue-600 rounded-full mr-2"></span> Status Kualitas Air
                    </h3>
                    <p class="text-sm text-gray-400 font-medium">Kualitas Air Saat Ini:</p>
                    <h2 id="kondisi-air-text" class="text-7xl font-black text-gray-300 my-4 tracking-tighter">{{ $latest->kondisi_air ?? '-' }}</h2>
                </div>
                <div>
                    <p class="text-xs text-gray-400 leading-relaxed italic">Hasil kualitas air tersebut dihitung menggunakan pendekatan Fuzzy Tsukamoto yang berdasarkan rules base (aturan) yang telah dibuat sebelumnya.</p>
                </div>
            </div>

            <div class="lg:col-span-8 bg-white p-8 rounded-2xl shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-gray-500 font-bold uppercase tracking-wider text-sm flex items-center">
                        <i class="fas fa-chart-area mr-2 text-blue-500"></i>Grafik Real-Time (Nilai Z)
                    </h3>
                    <span id="live-indicator" class="text-xs font-bold bg-gray-100 text-gray-400 px-3 py-1 rounded-full">OFFLINE</span>
                </div>
                <div class="relative w-full" style="height: 350px;">
                    <canvas id="fuzzyChart"></canvas>
                </div>
            </div>

            <div class="lg:col-span-12">
                <button id="btn-toggle" onclick="toggleMonitoring()" 
                    class="w-full py-5 rounded-2xl font-black text-xl shadow-xl transform transition hover:-translate-y-1 active:scale-[0.99] flex items-center justify-center text-white transition-colors duration-500 {{ $status == 'start' ? 'bg-red-500' : 'bg-green-500' }}">
                    <i id="btn-icon" class="fas {{ $status == 'start' ? 'fa-stop-circle' : 'fa-play-circle' }} mr-3"></i>
                    <span id="btn-text">{{ $status == 'start' ? 'HENTIKAN MONITORING SENSOR' : 'MULAI MONITORING SENSOR' }}</span>
                </button>
            </div>

            <div class="lg:col-span-4 bg-white p-8 rounded-2xl shadow-sm border-b-8 border-cyan-400">
                <span class="text-cyan-600 font-bold text-lg tracking-tight"><i class="fas fa-thermometer-half mr-2"></i>Suhu Air</span>
                <h4 class="text-5xl font-black text-slate-800"><span id="suhu-val">{{ $latest->suhu ?? 0 }}</span><span class="text-2xl text-slate-400 font-light">°C</span></h4>
            </div>

            <div class="lg:col-span-4 bg-white p-8 rounded-2xl shadow-sm border-b-8 border-indigo-400">
                <span class="text-indigo-600 font-bold text-lg tracking-tight"><i class="fas fa-vial mr-2"></i>Kadar pH</span>
                <h4 class="text-5xl font-black text-slate-800" id="ph-val">{{ $latest->ph ?? 0 }}</h4>
            </div>

            <div class="lg:col-span-4 bg-white p-8 rounded-2xl shadow-sm border-b-8 border-blue-400">
                <span class="text-blue-600 font-bold text-lg tracking-tight"><i class="fas fa-water mr-2"></i>Salinitas</span>
                <div class="flex items-baseline gap-2">
                    <h4 class="text-5xl font-black text-slate-800">
                        <span id="salinitas-val">{{ round($latest->salinitas ?? 0) }}</span><span class="text-xl text-slate-400 font-light ml-1">ppt</span>
                    </h4>
                    <div class="text-slate-400 font-medium text-sm italic">
                        (<span id="salinitas-ppm-val">{{ ($latest->salinitas ?? 0) * 1000 }}</span> ppm)
                    </div>
                </div>
            </div>

            <div class="lg:col-span-12 bg-white rounded-2xl shadow-sm overflow-hidden mb-10">
                <div class="p-6 border-b flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm flex items-center">
                        <i class="fas fa-history mr-2 text-blue-600"></i>Log Riwayat Terkini
                    </h3>
                    <a href="{{ route('export.log') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-green-700 transition flex items-center shadow-md shadow-green-100">
                        <i class="fas fa-file-excel mr-2"></i> Unduh Data
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-100 text-slate-600 uppercase text-xs font-bold">
                            <tr>
                                <th class="p-5">Waktu</th>
                                <th class="p-5 text-center">Suhu (°C)</th>
                                <th class="p-5 text-center">pH</th>
                                <th class="p-5 text-center">Salinitas (ppt)</th>
                                <th class="p-5">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body" class="divide-y divide-slate-100 text-sm"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('fuzzyChart').getContext('2d');
        let fuzzyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [], 
                datasets: [{
                    label: 'Nilai Fuzzy (Z)',
                    data: [], 
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fbbf24'
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });

        function toggleMonitoring() {
            $('#btn-text').text('MEMPROSES...');
            $.post("/toggle-status", { _token: "{{ csrf_token() }}" }, function(data) {
                updateButtonUI(data.status);
            });
        }

        function updateButtonUI(status) {
            const btn = $('#btn-toggle');
            const icon = $('#btn-icon');
            const text = $('#btn-text');
            const indicator = $('#live-indicator');

            if(status == 'start') {
                btn.removeClass('bg-green-500').addClass('bg-red-500');
                text.text('HENTIKAN MONITORING SENSOR');
                icon.removeClass('fa-play-circle').addClass('fa-stop-circle');
                indicator.text('LIVE DATA').removeClass('bg-gray-100 text-gray-400').addClass('bg-blue-50 text-blue-600 animate-pulse');
            } else {
                btn.removeClass('bg-red-500').addClass('bg-green-500');
                text.text('MULAI MONITORING SENSOR');
                icon.removeClass('fa-stop-circle').addClass('fa-play-circle');
                indicator.text('OFFLINE').removeClass('bg-blue-50 text-blue-600 animate-pulse').addClass('bg-gray-100 text-gray-400');
            }
        }

        function updateDashboard() {
            $.ajax({
                url: "{{ route('fetch.data') }}",
                method: "GET",
                success: function(response) {
                    if(!response.latest || response.chartValues.length === 0) {
                        fuzzyChart.data.labels = [];
                        fuzzyChart.data.datasets[0].data = [];
                        fuzzyChart.update();
                        return;
                    }

                    $('#suhu-val').text(response.latest.suhu);
                    $('#ph-val').text(response.latest.ph);

                    let valPPT = parseFloat(response.latest.salinitas);
                    $('#salinitas-val').text(Math.round(valPPT)); // Menghilangkan koma
                    $('#salinitas-ppm-val').text(Math.round(valPPT * 1000));

                    $('#kondisi-air-text').text(response.latest.kondisi_air);

                    let kClass = 'text-7xl font-black my-4 tracking-tighter ';
                    if(response.latest.kondisi_air == 'Baik') {
                        $('#kondisi-air-text').attr('class', kClass + 'text-green-500');
                    } else if(response.latest.kondisi_air == 'Sedang') {
                        $('#kondisi-air-text').attr('class', kClass + 'text-orange-500');
                    } else {
                        $('#kondisi-air-text').attr('class', kClass + 'text-red-500');
                    }

                    fuzzyChart.data.labels = response.chartLabels;
                    fuzzyChart.data.datasets[0].data = response.chartValues;
                    fuzzyChart.update('none');

                    let rows = '';
                    response.history.forEach((data) => {
                        let badgeColor = (data.kondisi_air == 'Baik') ? 'bg-green-100 text-green-700' : 
                                         (data.kondisi_air == 'Buruk' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700');

                        rows += `<tr class="hover:bg-blue-50/30 transition-colors">
                            <td class="p-5 font-semibold text-slate-700">${new Date(data.created_at).toLocaleTimeString('id-ID')}</td>
                            <td class="p-5 text-center font-bold text-blue-600">${data.suhu}</td>
                            <td class="p-5 text-center font-bold text-blue-600">${data.ph}</td>
                            <td class="p-5 text-center font-bold text-blue-600">${Math.round(data.salinitas)}</td>
                            <td class="p-5"><span class="px-4 py-1.5 rounded-lg text-xs font-black uppercase ${badgeColor}">${data.kondisi_air}</span></td>
                        </tr>`;
                    });
                    $('#table-body').html(rows);
                }
            });
        }

        $(document).ready(function() {
            updateButtonUI("{{ $status }}");
            updateDashboard();
            setInterval(updateDashboard, 5000);
        });
    </script>
</body>
</html>