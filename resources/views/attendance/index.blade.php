@extends('layouts.app')

@section('title', 'Face Recognition Attendance')

@section('content')
<div class="min-h-screen bg-[#ffffff] flex">
    <!-- Left Side -->
    <div class="w-1/2 flex items-center justify-center p-8">
        <div class="w-full max-w-2xl text-center">

            <!-- Logo -->
            <div class="mb-2 flex justify-center">
                <img src="{{ asset('logo-dark.png') }}" class="w-100 h-auto object-contain" alt="Logo">
            </div>

            <h2 class="text-3xl font-bold text-white mb-4">Absen Face Recognition</h2>

            <!-- Camera -->
            <div class="bg-zinc-200 rounded-2xl overflow-hidden shadow-2xl mb-6 relative">
                <video id="video" autoplay playsinline class="w-full h-auto" style="transform: scaleX(-1);"></video>
                <canvas id="canvas" class="hidden"></canvas>
                
                <!-- Anti-Spoofing Status Overlay -->
                <div id="antiSpoofStatus" class="absolute top-4 left-4 px-4 py-2 rounded-lg font-bold text-sm shadow-lg hidden">
                    <span id="spoofLabel"></span>
                    <span id="spoofScore" class="text-xs ml-2"></span>
                </div>
            </div>

            <!-- Auto-fill NIK / Name -->
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-black text-sm mb-2">NIK</label>
                    <input id="nik" readonly class="w-full px-2 bg-zinc-200 rounded-lg" style="font-size: 30pt" />
                </div>

                <div>
                    <label class="block text-black text-sm mb-2">Nama</label>
                    <input id="nama" readonly class="w-full px-4 py-3 bg-zinc-200 rounded-lg" />
                </div>
            </div>

            <!-- Submit -->
            <button id="submitBtn" type="button" onclick="openSubmitModal()"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-lg shadow-lg transition hover:scale-105">
                SUBMIT
            </button>

            {{-- <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-blue-200 hover:text-white text-sm" target="_blank">Admin Login →</a>
            </div> --}}
        </div>
    </div>

    <!-- RIGHT SIDE LIST -->
    <div class="w-1/2 bg-white p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-900">DAFTAR ABSENSI</h2>
            <div class="text-right">
                <div class="text-sm text-gray-500">{{ now()->format('d F Y') }}</div>
                <div id="clock" class="text-4xl font-bold text-gray-900"></div>
            </div>
        </div>

        <!-- Meal Type -->
        <div class="mb-4">
            @if($currentMealType)
                <span class="px-4 py-2 rounded-full text-sm font-bold
                    @if($currentMealType === 'breakfast') bg-yellow-100 text-yellow-800
                    @elseif($currentMealType === 'lunch') bg-blue-100 text-blue-800
                    @else bg-purple-100 text-purple-800
                    @endif">
                    Current: {{ ucfirst($currentMealType) }}
                </span>
            @else
                <span class="px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-800">
                    Jam makan sudah tutup
                </span>
            @endif
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto" style="max-height: calc(100vh - 250px);">
                <table class="min-w-full">
                    <thead class="bg-blue-100 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold">No.</th>
                            <th class="px-4 py-3 text-xs font-bold">NIK</th>
                            <th class="px-4 py-3 text-xs font-bold">Nama</th>
                            <th class="px-4 py-3 text-xs font-bold">Tanggal & Waktu</th>
                            <th class="px-4 py-3 text-xs font-bold">Jumlah</th>
                            <th class="px-4 py-3 text-xs font-bold">Kategori</th>
                            <th class="px-4 py-3 text-xs font-bold">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @foreach($attendances as $i => $a)
                        <tr>
                            <td class="px-4 py-3">{{ $i+1 }}</td>
                            <td class="px-4 py-3">{{ $a->nik }}</td>
                            <td class="px-4 py-3">{{ $a->employee->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $a->attendance_time->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $a->quantity }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($a->meal_type==='breakfast') bg-yellow-100 text-yellow-800
                                    @elseif($a->meal_type==='lunch') bg-blue-100 text-blue-800
                                    @else bg-purple-100 text-purple-800 @endif">
                                    {{ ucfirst($a->meal_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($a->is_real_face === null)
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">N/A</span>
                                @elseif($a->is_real_face)
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">✓ REAL</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">⚠ FAKE</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500">
            © 2025 IT-SIMS. All rights reserved
        </div>
    </div>
</div>

<!-- SUBMIT MODAL -->
<div id="submitModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-md rounded-2xl p-8 shadow-xl relative">

        <!-- Tombol Exit -->
        <button type="button" onclick="closeSubmitModal()"
            class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl font-bold" aria-label="Tutup">
            ✕
        </button>

        <h2 class="text-2xl font-bold text-center mb-4">SUBMIT ABSENSI</h2>

        <label class="block mb-2 font-semibold text-gray-700">Jumlah Makanan</label>
        <input id="modalQuantity" type="number" value="1" min="1" max="10"
            class="w-full px-3 py-3 border rounded-lg mb-4">

        <label class="block mb-2 font-semibold text-gray-700">Rating Makanan</label>
        <div id="ratingStars" class="flex gap-6 mb-6 text-5xl cursor-pointer select-none">
            <span data-value="1" class="text-gray-300">★</span>
            <span data-value="2" class="text-gray-300">★</span>
            <span data-value="3" class="text-gray-300">★</span>
            <span data-value="4" class="text-gray-300">★</span>
            <span data-value="5" class="text-gray-300">★</span>
        </div>

<!-- simpan nilai rating -->
<input type="hidden" id="ratingValue" value="">

        {{-- <label class="block mb-2 font-semibold text-gray-700">Saran</label> --}}
        {{-- <textarea id="modalRemarks" rows="4"
            class="w-full px-3 py-3 border rounded-lg mb-6"
            placeholder="Opsional"></textarea> --}}

        <button type="button" id="btnKirim" onclick="submitAttendance()"
            class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-bold">
            KIRIM
        </button>

    </div>
</div>

<!-- GENERIC MODAL -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
        <div id="modalContent" class="text-center"></div>
    </div>
</div>

<!-- CSRF meta jika layout belum menyertakan -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
    document.addEventListener('focusin', function (e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        try {
            window.location.href = "ms-inputapp:";
        } catch (err) {}
    }
});
/* =============================
      CLOCK
============================= */
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
        now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
}
setInterval(updateClock, 1000);
updateClock();

// --- Rating Bintang ---
const stars = document.querySelectorAll("#ratingStars span");
const ratingInput = document.getElementById("ratingValue");

stars.forEach(star => {
    star.addEventListener("click", () => {
        const rating = star.getAttribute("data-value");
        ratingInput.value = rating; // simpan nilai rating

        // update tampilan bintang
        stars.forEach(s => {
            if (s.getAttribute("data-value") <= rating) {
                s.classList.remove("text-gray-300");
                s.classList.add("text-yellow-400");
            } else {
                s.classList.remove("text-yellow-400");
                s.classList.add("text-gray-300");
            }
        });
    });
});


/* =============================
      CAMERA
============================= */
const video = document.getElementById("video");
const canvas = document.getElementById("canvas");
const nikInput = document.getElementById("nik");
const namaInput = document.getElementById("nama");

let isRecognizing = false;
let lastRecognized = null;

// Start camera safely
if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
            video.srcObject = stream;
        })
        .catch(err => {
            console.error("Camera error:", err);
        });
} else {
    console.warn("getUserMedia not supported");
}

/* =============================
      AUTO RECOGNITION
============================= */
setInterval(async () => {
    if (isRecognizing) return;
    isRecognizing = true;

    try {
        if (!video.videoWidth || !video.videoHeight) return;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext("2d");
        ctx.save();
        ctx.scale(-1, 1);
        ctx.drawImage(video, -canvas.width, 0);
        ctx.restore();

        const base64 = canvas.toDataURL("image/jpeg");

        const res = await fetch("{{ route('checkin') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                image: base64,
                recognize_only: true
            })
        });

        const data = await res.json();

        // Debug: log response data
        console.log("Recognition response:", data);

        // Update Anti-Spoofing Status Display
        const statusDiv = document.getElementById("antiSpoofStatus");
        const labelSpan = document.getElementById("spoofLabel");
        const scoreSpan = document.getElementById("spoofScore");

        if (data.is_real_face !== null && data.is_real_face !== undefined) {
            statusDiv.classList.remove("hidden");
            
            if (data.is_real_face) {
                // Real Face
                statusDiv.classList.remove("bg-red-500");
                statusDiv.classList.add("bg-green-500", "text-white");
                labelSpan.textContent = "🔒 REAL";
                scoreSpan.textContent = `(${data.anti_spoofing_score?.toFixed(3) || ''})`;
            } else {
                // Fake Face
                statusDiv.classList.remove("bg-green-500");
                statusDiv.classList.add("bg-red-500", "text-white");
                labelSpan.textContent = "⚠️ FAKE";
                scoreSpan.textContent = `(${data.anti_spoofing_score?.toFixed(3) || ''})`;
            }
        } else {
            // No anti-spoofing data
            statusDiv.classList.add("hidden");
        }

        if (data.success && data.nik && data.nik !== lastRecognized) {
            nikInput.value = data.nik;
            namaInput.value = data.employee_name || '';
            lastRecognized = data.nik;
        }

    } catch (e) {
        console.log("Recognition error:", e);
    } finally {
        isRecognizing = false;
    }

}, 2000);

/* =============================
      OPEN / CLOSE SUBMIT MODAL
============================= */
function openSubmitModal() {
    if (!nikInput.value) {
        return showModal("error", "Error", "Wajah belum terdeteksi!");
        // setTimeout(() => {
        //     modal.classList.add("hidden");
        //     location.reload();
        // }, 3000);
    }
    document.getElementById("submitModal").classList.remove("hidden");
}

function closeSubmitModal() {
    const modal = document.getElementById("submitModal");
    if (modal) modal.classList.add("hidden");
}

// Tutup modal submit saat klik di luar konten modal (overlay)
const submitModalEl = document.getElementById('submitModal');
if (submitModalEl) {
    submitModalEl.addEventListener('click', function(e) {
        if (e.target === this) closeSubmitModal();
    });
}

// Tutup modal dengan tombol Esc
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSubmitModal();
        closeModal(false);
    }
});

/* =============================
      SUBMIT ATTENDANCE
============================= */
async function submitAttendance() {
    const btn = document.getElementById("btnKirim");
    btn.disabled = true;
    btn.innerText = "PROSES...";
    btn.classList.add("opacity-50", "cursor-not-allowed");

    const quantity = document.getElementById("modalQuantity").value;
    const rating = document.getElementById("ratingValue").value;
    // const remarks  = document.getElementById("modalRemarks").value;

    if (!nikInput.value) {
        btn.disabled = false;
        btn.innerText = "KIRIM";
        btn.classList.remove("opacity-50", "cursor-not-allowed");

        closeSubmitModal();
        return showModal("error", "Error", "NIK kosong. Coba ulang deteksi wajah.");
    }

    // ambil gambar current
    if (!video.videoWidth || !video.videoHeight) {
        btn.disabled = false;
        btn.innerText = "KIRIM";
        btn.classList.remove("opacity-50", "cursor-not-allowed");

        return showModal("error", "Error", "Kamera belum siap.");
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext("2d");
    ctx.save();
    ctx.scale(-1, 1);
    ctx.drawImage(video, -canvas.width, 0);
    ctx.restore();

    const imageData = canvas.toDataURL("image/jpeg");

    try {
        const res = await fetch("{{ route('checkin') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                image: imageData,
                quantity: quantity,
                rating: rating,
                // remarks: remarks,
                recognize_only: false
            })
        });

        const data = await res.json();

        // aktifkan tombol kembali
        btn.disabled = false;
        btn.innerText = "KIRIM";
        btn.classList.remove("opacity-50", "cursor-not-allowed");

        if (data.success) {
            closeSubmitModal();
            showModal("success", "Berhasil", data.message, true);
            setTimeout(() => {
                modal.classList.add("hidden");
                location.reload();
            }, 3000);
        } else {
            closeSubmitModal();
            showModal("error", "Gagal", data.message);
            setTimeout(() => {
                modal.classList.add("hidden");
                location.reload();
            }, 3000);
        }

    } catch (e) {
        console.error("Submit error:", e);

        // aktifkan tombol kembali walau error
        btn.disabled = false;
        btn.innerText = "KIRIM";
        btn.classList.remove("opacity-50", "cursor-not-allowed");

        showModal("error", "Gagal", "Terjadi kesalahan. Coba lagi.");
        setTimeout(() => {
            modal.classList.add("hidden");
            location.reload();
        }, 3000);
    }
}


/* =============================
      GENERIC MODAL
============================= */
function showModal(type, title, message, reload = false) {
    const modal = document.getElementById("modal");

    let icon = "";

    if (type === "success") {
        icon = `
            <svg class="w-14 h-14 text-green-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12l2 2l4 -4M12 22c5.523 0 10 -4.477 10 -10S17.523 2 12 2S2 6.477 2 12s4.477 10 10 10z"/>
            </svg>
        `;
    } else if (type === "error") {
        icon = `
            <svg class="w-14 h-14 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v4m0 4h.01M12 2a10 10 0 100 20a10 10 0 000-20z"/>
            </svg>
        `;
    } else if (type === "warning") {
        icon = `
            <svg class="w-14 h-14 text-yellow-500 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01M21 18H3l9-15l9 15z"/>
            </svg>
        `;
    } else {
        // default info
        icon = `
            <svg class="w-14 h-14 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8h.01M12 12v4m0 6a10 10 0 110-20a10 10 0 010 20z"/>
            </svg>
        `;
    }

    const html = `
        <div class="text-center px-6 py-4">
            ${icon}
            <h3 class="text-2xl font-bold mb-2">${title}</h3>
            <p class="text-gray-600 mb-6">${message}</p>
            <button type="button" onclick="closeModal(${reload})"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg">
                OK
            </button>
        </div>
    `;

    document.getElementById("modalContent").innerHTML = html;

    if (modal) modal.classList.remove("hidden");
}


function closeModal(reload=false) {
    const modal = document.getElementById("modal");
    if (modal) modal.classList.add("hidden");
    if (reload) location.reload();
}
</script>

@endsection
