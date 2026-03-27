<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuzzy Rules - Monitoring Kepiting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">

    <div class="w-full min-h-screen p-4 md:p-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">Fuzzy Rules</h1>
                <p class="text-gray-600 font-medium text-center md:text-left">Basis Pengetahuan (Knowledge Base) Metode Tsukamoto</p>
            </div>
            <div class="flex gap-2">
                <a href="/" class="bg-white px-5 py-2.5 rounded-lg shadow-sm text-gray-600 font-bold hover:bg-gray-50 flex items-center transition-all active:scale-95">
                    <i class="fas fa-home mr-2 text-blue-500"></i>Back to Home
                </a>
                <button onclick="openModal('add')" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow-lg font-bold hover:bg-blue-700 transition-all active:scale-95">
                    <i class="fas fa-plus mr-2"></i>Tambah Aturan
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-600 uppercase text-xs font-bold border-b">
                        <tr>
                            <th class="p-5">Kode</th>
                            <th class="p-5 text-center">Suhu</th>
                            <th class="p-5 text-center">pH</th>
                            <th class="p-5 text-center">Salinitas</th>
                            <th class="p-5 text-center bg-gray-50">Output</th>
                            <th class="p-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rules as $rule)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-5 text-blue-900 font-black">{{ $rule->kode_rule }}</td>
                            
                            <td class="p-5 text-center text-gray-700 font-medium">{{ $rule->suhu }}</td>
                            <td class="p-5 text-center text-gray-700 font-medium">{{ $rule->ph }}</td>
                            <td class="p-5 text-center text-gray-700 font-medium">{{ $rule->salinitas }}</td>
                            
                            <td class="p-5 text-center bg-gray-50/50">
                                @if($rule->output == 'Baik')
                                    <span class="bg-green-100 text-green-700 px-4 py-1.5 rounded-lg text-xs font-black border border-green-200 uppercase">
                                        {{ $rule->output }}
                                    </span>
                                @elseif($rule->output == 'Sedang' || $rule->output == 'Normal')
                                    <span class="bg-orange-100 text-orange-700 px-4 py-1.5 rounded-lg text-xs font-black border border-orange-200 uppercase">
                                        {{ $rule->output }}
                                    </span>
                                @else
                                    <span class="bg-red-100 text-red-700 px-4 py-1.5 rounded-lg text-xs font-black border border-red-200 uppercase">
                                        {{ $rule->output }}
                                    </span>
                                @endif
                            </td>

                            <td class="p-5 text-center flex justify-center gap-2">
                                <button onclick="editRule({{ json_encode($rule) }})" class="text-yellow-500 hover:bg-yellow-50 p-2 rounded-lg transition" title="Edit Rule">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="/fuzzy-rules/{{ $rule->id }}" method="POST" onsubmit="return confirm('Hapus aturan R{{ $rule->kode_rule }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition" title="Hapus Rule">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-gray-400 italic">Belum ada aturan fuzzy yang dibuat.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalRule" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 p-4 backdrop-blur-sm transition-all">
        <div class="bg-white w-full max-w-md rounded-2xl p-8 shadow-2xl transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-xl font-black text-slate-800">Tambah Aturan Baru</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <form id="ruleForm" action="/fuzzy-rules" method="POST" class="space-y-5">
                @csrf
                <div id="methodField"></div> 
                
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400 mb-1 block">Kode Rule (Angka)</label>
                        <input type="number" name="kode_rule" id="kode_rule" placeholder="Contoh: 1" 
                               class="w-full border rounded-lg p-3 bg-gray-50 focus:ring-2 focus:ring-blue-500 outline-none font-bold text-blue-900" required>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400 mb-1 block">Suhu</label>
                        <select name="suhu" id="suhu" class="w-full border rounded-lg p-3 bg-gray-50 focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-gray-700">
                            <option value="Baik">Baik</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Buruk">Buruk</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400 mb-1 block">pH</label>
                        <select name="ph" id="ph" class="w-full border rounded-lg p-3 bg-gray-50 focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-gray-700">
                            <option value="Baik">Baik</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Buruk">Buruk</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400 mb-1 block">Salinitas</label>
                        <select name="salinitas" id="salinitas" class="w-full border rounded-lg p-3 bg-gray-50 focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-gray-700">
                            <option value="Baik">Baik</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Buruk">Buruk</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400 mb-1 block text-blue-600">Output (Kondisi Air)</label>
                        <select name="output" id="output" class="w-full border-2 border-blue-50 rounded-lg p-3 bg-white focus:ring-2 focus:ring-blue-500 outline-none font-bold text-gray-800">
                            <option value="Baik" class="text-green-600">Baik</option>
                            <option value="Sedang" class="text-orange-600">Sedang</option>
                            <option value="Buruk" class="text-red-600">Buruk</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 pt-4">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 py-3 rounded-xl font-bold text-gray-500 hover:bg-gray-200 transition">Batal</button>
                    <button type="submit" id="btnSubmit" class="flex-1 bg-blue-600 py-3 rounded-xl font-bold text-white shadow-lg shadow-blue-200 hover:bg-blue-700 transition">Simpan Aturan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode) {
            $('#modalRule').removeClass('hidden');
            if(mode === 'add') {
                $('#modalTitle').text('Tambah Aturan Baru');
                $('#ruleForm').attr('action', '/fuzzy-rules');
                $('#methodField').html(''); 
                $('#ruleForm')[0].reset();
            }
        }

        function closeModal() {
            $('#modalRule').addClass('hidden');
        }

        function editRule(rule) {
            openModal('edit');
            $('#modalTitle').text('Edit Aturan R' + rule.kode_rule);
            $('#ruleForm').attr('action', '/fuzzy-rules/' + rule.id);
            $('#methodField').html('@method("PUT")'); 
            
            $('#kode_rule').val(rule.kode_rule);
            $('#suhu').val(rule.suhu);
            $('#ph').val(rule.ph);
            $('#salinitas').val(rule.salinitas);
            $('#output').val(rule.output);
        }

        $(window).click(function(event) {
            if (event.target == document.getElementById('modalRule')) {
                closeModal();
            }
        });
    </script>
</body>
</html>