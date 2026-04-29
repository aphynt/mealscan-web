@extends('layouts.app')

@section('title', 'Face Recognition Attendance')

@section('content')
{{-- <div class="min-h-screen bg-[#ffffff] flex"> --}}
<div class="min-h-screen labor-page relative overflow-hidden flex">
    <div class="labor-pattern"></div>
    {{-- <div class="labor-top-ribbon">SELAMAT HARI BURUH NASIONAL</div> --}}
    <div class="labor-shape labor-shape-1"></div>
    <div class="labor-shape labor-shape-2"></div>
    <div class="labor-shape labor-shape-3"></div>
    <!-- Left Side -->
    <div class="w-1/2 flex items-center justify-center p-8 left-panel">
        <div class="w-full max-w-2xl text-center glass-card rounded-3xl p-8">

            <!-- Logo -->
            <div class="mb-2 flex justify-center">
                <img src="{{ asset('logo-dark.png') }}" class="w-100 h-auto object-contain" alt="Logo">
            </div>

            <h2 class="text-3xl font-bold text-black mb-4">Absen Face Recognition</h2>

            <!-- Camera -->
            <div class="camera-frame rounded-2xl overflow-hidden mb-6 relative">
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
                    <input id="nik" readonly class="w-full px-2 rounded-lg input-soft" style="font-size: 30pt" />
                </div>

                <div>
                    <label class="block text-black text-sm mb-2">Nama</label>
                    <input id="nama" readonly class="w-full px-4 py-3 rounded-lg input-soft" />
                </div>
            </div>

            <!-- Submit -->
            <button id="submitBtn" type="button" onclick="openSubmitModal()"
                class="w-full submit-labor text-white font-bold py-4 rounded-lg transition hover:scale-105">
                SUBMIT
            </button>

            {{-- <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-blue-200 hover:text-white text-sm" target="_blank">Admin Login →</a>
            </div> --}}
        </div>
    </div>

    <!-- RIGHT SIDE LIST -->
    <div class="w-1/2 p-8 right-panel">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl section-title">DAFTAR ABSENSI</h2>
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
        <div class="attendance-card rounded-xl overflow-hidden">
            <div class="overflow-x-auto" style="max-height: calc(100vh - 250px);">
                <table class="min-w-full">
                    <thead class="attendance-head sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold">No.</th>
                            <th class="px-4 py-3 text-xs font-bold">NIK</th>
                            <th class="px-4 py-3 text-xs font-bold">Nama</th>
                            <th class="px-4 py-3 text-xs font-bold">Tanggal & Waktu</th>
                            <th class="px-4 py-3 text-xs font-bold">Jumlah</th>
                            <th class="px-4 py-3 text-xs font-bold">Order Type</th>
                            <th class="px-4 py-3 text-xs font-bold">Kategori</th>
                            <th class="px-4 py-3 text-xs font-bold">Wajah</th>
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
                            <td class="px-4 py-">{{ $a->order_type }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($a->meal_type==='breakfast') bg-yellow-100 text-yellow-800
                                    @elseif($a->meal_type==='lunch') bg-blue-100 text-blue-800
                                    @else bg-purple-100 text-purple-800 @endif">
                                    {{ ucfirst($a->meal_type) }}
                                </span>
                            </td>
                            {{-- <td class="px-4 py-3">
                                @if($a->is_real_face === null)
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">N/A</span>
                                @elseif($a->is_real_face)
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">✓ REAL</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">⚠ FAKE</span>
                                @endif
                            </td> --}}
                            <td class="px-4 py-3">
                                @if($a->is_real_face === null)
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">N/A</span>
                                @elseif($a->is_real_face)
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">✓</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">⚠</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>

        <div class="mt-6 text-center text-sm footer-soft">
            © 2025 IT-SIMS. All rights reserved
        </div>
    </div>
</div>

<!-- SUBMIT MODAL -->
<div id="submitModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-md rounded-2xl p-8 shadow-xl relative">

        <!-- Tombol Exit -->
        <button type="button" onclick="closeSubmitModal()"
            class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl font-bold">
            ✕
        </button>

        <h2 class="text-2xl font-bold text-center mb-6">SUBMIT ABSENSI</h2>

        <!-- ================= JUMLAH MAKANAN ================= -->
        <label class="block mb-2 font-semibold text-gray-700">
            Jumlah Makanan
        </label>

        <div class="flex items-center justify-between mb-6">

            <button type="button"
                onclick="changeQty(-1)"
                class="w-12 h-12 rounded-lg bg-gray-200 hover:bg-gray-300 text-2xl font-bold transition">
                −
            </button>

            <input id="modalQuantity"
                type="number"
                value="1"
                min="1"
                max="10"
                readonly
                class="w-24 text-center px-3 py-3 border rounded-lg font-semibold text-lg">

            <button type="button"
                onclick="changeQty(1)"
                class="w-12 h-12 rounded-lg bg-gray-200 hover:bg-gray-300 text-2xl font-bold transition">
                +
            </button>

        </div>

        <!-- ================= TIPE AMBIL MAKANAN ================= -->
        <label class="block mb-2 font-semibold text-gray-700">
            Tipe Ambil Makanan
        </label>

        <div class="grid grid-cols-2 gap-3 mb-6">
            <button type="button"
                id="btnDineIn"
                onclick="setOrderType('Dine In')"
                class="order-type-btn bg-green-600 text-white border border-green-600 py-3 rounded-lg font-bold transition">
                Makan Di Kantin
            </button>

            <button type="button"
                id="btnTakeAway"
                onclick="setOrderType('Take Away')"
                class="order-type-btn bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 py-3 rounded-lg font-bold transition">
                Bungkus
            </button>
        </div>

        <input type="hidden" id="orderType" value="Dine In">

        <!-- ================= RATING MAKANAN ================= -->
        <label class="block mb-2 font-semibold text-gray-700">
            Rating Makanan
        </label>

        <div id="ratingStars"
            class="flex justify-between mb-3 text-4xl cursor-pointer select-none">

            <span data-value="1" class="star text-gray-300 transition duration-200">★</span>
            <span data-value="2" class="star text-gray-300 transition duration-200">★</span>
            <span data-value="3" class="star text-gray-300 transition duration-200">★</span>
            <span data-value="4" class="star text-gray-300 transition duration-200">★</span>
            <span data-value="5" class="star text-gray-300 transition duration-200">★</span>

        </div>

        <p id="ratingText" class="text-center text-sm text-gray-600 mb-6 italic">
            Belum ada rating
        </p>

        <input type="hidden" id="ratingValue" value="">

        <!-- ================= BUTTON ================= -->
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
<style>
    .labor-page {
        position: relative;
        background:
            linear-gradient(135deg, #fff7f7 0%, #ffffff 35%, #f5f9ff 100%);
    }

    .labor-page > * {
        position: relative;
        z-index: 2;
    }

    .labor-pattern {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background:
            radial-gradient(circle at 10% 15%, rgba(220, 38, 38, 0.14) 0, transparent 18%),
            radial-gradient(circle at 85% 20%, rgba(37, 99, 235, 0.12) 0, transparent 20%),
            radial-gradient(circle at 75% 80%, rgba(22, 163, 74, 0.10) 0, transparent 16%),
            linear-gradient(to bottom, rgba(255,255,255,0.65), rgba(255,255,255,0.88)),
            url("data:image/svg+xml,%3Csvg width='1200' height='700' viewBox='0 0 1200 700' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23dc2626' stroke-opacity='0.08' stroke-width='2'%3E%3Cpath d='M0 630H1200'/%3E%3Cpath d='M80 630V420H150V630'/%3E%3Cpath d='M190 630V380H280V630'/%3E%3Cpath d='M320 630V450H390V630'/%3E%3Cpath d='M430 630V350H520V630'/%3E%3Cpath d='M560 630V400H640V630'/%3E%3Cpath d='M690 630V370H780V630'/%3E%3Cpath d='M820 630V430H900V630'/%3E%3Cpath d='M940 630V390H1030V630'/%3E%3Cpath d='M1070 630V450H1140V630'/%3E%3Cpath d='M215 380l35-55 35 55'/%3E%3Cpath d='M465 350l40-65 40 65'/%3E%3Cpath d='M725 370l35-60 35 60'/%3E%3Cpath d='M965 390l35-60 35 60'/%3E%3C/g%3E%3Cg fill='%23dc2626' fill-opacity='0.05'%3E%3Ccircle cx='160' cy='120' r='60'/%3E%3Ccircle cx='1020' cy='140' r='75'/%3E%3C/g%3E%3C/svg%3E");
        background-size: cover;
        background-position: center;
    }

    .labor-top-ribbon {
        position: absolute;
        top: 18px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 5;
        padding: 12px 26px;
        border-radius: 999px;
        background: linear-gradient(90deg, #dc2626, #b91c1c);
        color: #fff;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 1.5px;
        box-shadow: 0 12px 30px rgba(185, 28, 28, 0.28);
        white-space: nowrap;
    }

    .labor-shape {
        position: absolute;
        border-radius: 999px;
        filter: blur(10px);
        opacity: 0.55;
        z-index: 1;
        pointer-events: none;
    }

    .labor-shape-1 {
        width: 220px;
        height: 220px;
        background: rgba(220, 38, 38, 0.13);
        top: 90px;
        left: -60px;
    }

    .labor-shape-2 {
        width: 260px;
        height: 260px;
        background: rgba(37, 99, 235, 0.10);
        bottom: 40px;
        right: -70px;
    }

    .labor-shape-3 {
        width: 180px;
        height: 180px;
        background: rgba(22, 163, 74, 0.10);
        top: 45%;
        right: 35%;
    }

    .labor-page .left-panel,
    .labor-page .right-panel {
        position: relative;
        z-index: 2;
    }

    .labor-page .glass-card {
        background: rgba(255, 255, 255, 0.70);
        border: 1px solid rgba(255, 255, 255, 0.55);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.10);
    }

    .labor-page .camera-frame {
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
    }

    .labor-page .input-soft {
        background: rgba(243, 244, 246, 0.85);
        border: 1px solid rgba(229, 231, 235, 0.95);
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .labor-page .submit-labor {
        background: linear-gradient(90deg, #16a34a, #15803d);
        box-shadow: 0 12px 24px rgba(22, 163, 74, 0.25);
    }

    .labor-page .submit-labor:hover {
        background: linear-gradient(90deg, #15803d, #166534);
    }

    .labor-page .attendance-card {
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(255, 255, 255, 0.65);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
    }

    .labor-page .attendance-head {
        background: linear-gradient(90deg, rgba(219, 234, 254, 0.95), rgba(254, 242, 242, 0.95));
    }

    .labor-page table tbody tr {
        background: rgba(255, 255, 255, 0.55);
        transition: 0.2s ease;
    }

    .labor-page table tbody tr:hover {
        background: rgba(254, 242, 242, 0.95);
    }

    .labor-page .title-main {
        background: linear-gradient(90deg, #b91c1c, #1d4ed8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .labor-page .section-title {
        color: #111827;
        font-weight: 800;
    }

    .labor-page .footer-soft {
        color: #6b7280;
    }

    @media (max-width: 1024px) {
        .labor-top-ribbon {
            font-size: 11px;
            padding: 10px 16px;
            top: 12px;
        }

        .labor-shape-3 {
            display: none;
        }
    }
</style>
<script>

// ================= QUANTITY =================
function changeQty(amount) {
    const input = document.getElementById("modalQuantity");
    let current = parseInt(input.value);

    current += amount;

    if (current < 1) current = 1;
    if (current > 10) current = 10;

    input.value = current;
}
document.addEventListener("DOMContentLoaded", function () {

    const stars = document.querySelectorAll("#ratingStars .star");
    const ratingText = document.getElementById("ratingText");
    const ratingInput = document.getElementById("ratingValue");

    const ratingDescriptions = {
        1: "Sangat Tidak Enak",
        2: "Kurang Enak",
        3: "Cukup",
        4: "Enak",
        5: "Sangat Enak"
    };

    let currentRating = 0;

    stars.forEach(star => {

        star.addEventListener("mouseover", function () {
            highlightStars(this.dataset.value);
        });

        star.addEventListener("mouseout", function () {
            highlightStars(currentRating);
        });

        star.addEventListener("click", function () {

            currentRating = parseInt(this.dataset.value);

            // ✅ Simpan ke hidden input
            ratingInput.value = currentRating;

            highlightStars(currentRating);

            ratingText.textContent = ratingDescriptions[currentRating];

        });

    });

    function highlightStars(rating) {
        stars.forEach(star => {
            if (parseInt(star.dataset.value) <= rating) {
                star.classList.remove("text-gray-300");
                star.classList.add("text-yellow-400");
            } else {
                star.classList.remove("text-yellow-400");
                star.classList.add("text-gray-300");
            }
        });
    }

});

function setOrderType(type) {
    const orderTypeInput = document.getElementById("orderType");
    const btnDineIn = document.getElementById("btnDineIn");
    const btnTakeAway = document.getElementById("btnTakeAway");

    orderTypeInput.value = type;

    if (type === "DINE_IN") {
        btnDineIn.className =
            "order-type-btn bg-green-600 text-white border border-green-600 py-3 rounded-lg font-bold transition";

        btnTakeAway.className =
            "order-type-btn bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 py-3 rounded-lg font-bold transition";
    } else {
        btnTakeAway.className =
            "order-type-btn bg-green-600 text-white border border-green-600 py-3 rounded-lg font-bold transition";

        btnDineIn.className =
            "order-type-btn bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 py-3 rounded-lg font-bold transition";
    }
}

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
    const orderType = document.getElementById("orderType").value;
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
                order_type: orderType,
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

function showModal(type, title, message, reload = false) {

    const modal = document.getElementById("modal");

    // ================= CONFIG TIPE =================
    const config = {
        success: {
            color: "#22c55e",
            button: "#f2a900",
            animation: "/images/success.json"
        },
        error: {
            color: "#ef4444",
            button: "#dc2626",
            animation: "/images/error.json"
        },
        warning: {
            color: "#facc15",
            button: "#f59e0b",
            animation: "/images/warning.json"
        },
        info: {
            color: "#3b82f6",
            button: "#2563eb",
            animation: "/images/info.json"
        }
    };

    const selected = config[type] || config.info;

    // ================= HTML =================
    const html = `
        <div class="text-center px-6 py-6">

            <div id="modalAnimation"
                 style="width:120px;height:120px;margin:auto;margin-bottom:15px">
            </div>

            <h3 class="text-2xl font-bold mb-2" style="color:${selected.color}">
                ${title}
            </h3>

            <p class="text-gray-600 mb-6">
                ${message}
            </p>

            <button type="button"
                onclick="closeModal(${reload})"
                style="
                    background:${selected.button};
                    color:#000;
                    padding:12px 28px;
                    border:none;
                    border-radius:8px;
                    font-weight:600;
                    cursor:pointer;
                ">
                OK
            </button>
        </div>
    `;

    document.getElementById("modalContent").innerHTML = html;

    if (modal) {
        modal.classList.remove("hidden");
        modal.classList.add("animate-fadeIn");
    }

    // ================= LOAD LOTTIE =================
    setTimeout(() => {
        const container = document.getElementById("modalAnimation");

        if (container && typeof lottie !== "undefined") {
            lottie.loadAnimation({
                container: container,
                renderer: "svg",
                loop: true, // <-- ubah ke true
                autoplay: true,
                path: selected.animation
            });
        }
    }, 100);
}


function closeModal(reload=false) {
    const modal = document.getElementById("modal");
    if (modal) modal.classList.add("hidden");
    if (reload) location.reload();
}
</script>

@endsection
