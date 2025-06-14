<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Points;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PointController extends Controller
{
    protected $pointsModel;

    public function __construct()
    {
        $this->pointsModel = new Points();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil semua poin dari model
        $points = $this->pointsModel->points(); // asumsi ada method points()

        $features = [];
        foreach ($points as $p) {
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $p->id,
                    'warga' => $p->warga,
                    'type' => $p->type,
                    'description' => $p->description,
                    'image' => $p->image,
                    'address' => $p->address,
                    'bukti' => $p->bukti,
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'warga'       => 'required|string|max:50',
            'type' => 'required|string|in:' . implode(',', \App\Models\Points::$types),
            'date'        => 'required|date',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
        ], [

            'image.mimes' => 'Image must be jpg, jpeg, png, or gif and not larger than 10MB',
        ]);

        // Upload image jika ada
        $filename = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_point.' . $file->getClientOriginalExtension();
            $file->storeAs('public/images', $filename);
        }

        // Simpan data
        $data = [
            'warga' => $request->warga,
            'type' => $request->type,
            'description' => $request->description,
            'image' => $filename,
            'address' => $request->address,
            'date'      => $request->date,
        ];

        $created = $this->pointsModel->create($data);
        if (!$created) {
            return redirect()->back()->with('error', 'Lengkapi kembali laporan Anda!');
        }

        return redirect()->back()->with('success', 'Laporan Anda sudah diterima, petugas berwenang akan segera menanganinya!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $point = $this->pointsModel->point($id); // asumsi method point() mengembalikan collection atau model
        if (!$point) {
            return response()->json(['message' => 'Point not found'], 404);
        }

        $features = [];
        foreach ((array) $point as $p) {
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $p->id,
                    'warga' => $p->warga,
                    'type' => $p->type,
                    'description' => $p->description,
                    'image' => $p->image,
                    'address' => $p->address,
                    'bukti' => $p->bukti,
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $point = Points::findOrFail($id);
        return view('laporan.edit', compact('point'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', \App\Models\Points::$types),
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
        ]);

        $point = $this->pointsModel->find($id);
        if (!$point) {
            return redirect()->back()->with('error', 'Point tidak ditemukan');
        }

        // Upload image baru jika ada
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_point.' . $file->getClientOriginalExtension();
            $file->storeAs('public/images', $filename);

            // Hapus file lama jika ada
            if ($point->image) {
                \Storage::delete('public/images/' . $point->image);
            }
        } else {
            $filename = $point->image;
        }

        // Update data
        $point->update([
            'type' => $request->type,
            'description' => $request->description,
            'image' => $filename,
            'address' => $request->address,
            'date'      => $request->date,
        ]);

        return redirect()->back()->with('success', 'Laporan berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $point = $this->pointsModel->find($id);
        if (!$point) {
            return redirect()->back()->with('error', 'Laporan tidak ditemukan');
        }

        // Hapus file gambar
        if ($point->image) {
            \Storage::delete('public/images/' . $point->image);
        }

        $point->delete();

        return redirect()->back()->with('success', 'Laporan Telah Dihapus');
    }

    /**
     * Show data tabel.
     */
    public function table(Request $request)
    {
        // semua status yang tersedia 
        $allStatuses = ['menunggu','diproses','selesai','ditolak'];

        // daftar bulan untuk dropdown
        $allMonths = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
        ];

        // query dasar
        $query = Points::orderBy('created_at','asc');

        // filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // filter bulan 
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        $points = $query->get();

        return view('table-point', [
            'title'       => 'Data Laporan',
            'points'      => $points,
            'allStatuses' => $allStatuses,
            'currentStatus' => $request->status,
            'allMonths'     => $allMonths,         
            'currentMonth'  => $request->month,    
        ]);
    }

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        // ambil parameter type, month, dan year (nullable)
        $type = $request->query('type');
        $month = $request->query('month');
        $year = $request->query('year');

        \Log::info('PDF Export Parameters:', [
            'type' => $type,
            'month' => $month,
            'year' => $year
        ]);

        // filter berdasarkan parameter yang diset
        $query = \App\Models\Points::query();
        
        if (!empty($type)) {
            $query->where('type', $type);
        }
        
        if (!empty($year)) {
            $query->whereYear('created_at', $year);
        }
        
        if (!empty($month)) {
            $query->whereMonth('created_at', $month);
        }
        
        $points = $query->orderBy('created_at', 'desc')->get();
        
        \Log::info('Query SQL:', [$query->toSql()]);
        \Log::info('Points count:', [count($points)]);

        $pdf = Pdf::loadView('laporan.pdf', compact('points', 'type', 'month', 'year'))
            ->setPaper('a4', 'landscape');

        // nama file kembalian, sertakan type, month, dan year kalau ada
        $filenameParts = ['laporan-gangguan'];
        
        if ($type) {
            $filenameParts[] = $type;
        }
        
        if ($year) {
            $filenameParts[] = $year;
        }
        
        if ($month) {
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $filenameParts[] = $monthName;
        }
        
        $filename = implode('-', $filenameParts) . '.pdf';

        return $pdf->download($filename);
    }

    public function updateStatus(Request $request, $id)
    {
        // 1. Validasi: pastikan 'status' wajib diisi 
        //    dan hanya boleh salah satu dari daftar berikut
        $validated = $request->validate([
            'status' => 'required|in:menunggu,diproses,selesai,ditolak',
        ], [
            'status.required' => 'Kolom status wajib diisi.',
            'status.in'       => 'Nilai status tidak valid.',
        ]);

        // 2. Cari record berdasarkan ID
        $point = $this->pointsModel->find($id);
        if (!$point) {
            return redirect()->back()->with('error', 'Laporan tidak ditemukan');
        }

        // 3. Jika status = "menunggu", cukup update saja
        if ($validated['status'] === 'menunggu') {
            $point->status = 'menunggu';
            $point->save();

            return redirect()->back()->with('success', 'Status laporan disetel menjadi “Menunggu.”');
        }

        // 4. Jika status = "ditolak", hapus data (dan file jika ada)
        if ($validated['status'] === 'ditolak') {
            $point->status = 'ditolak';
            $point->save();

            return redirect()->back()->with('success', 'Status laporan disetel menjadi “Ditolak.”');
        }

        // 5. Status lain (“diproses” atau “selesai”)
        $point->status = $validated['status'];
        $point->save();

        return redirect()->back()->with('success', 'Status laporan berhasil diperbarui.');
    }


    public function uploadBukti(Request $request, $id)
    {
        $request->validate([
            'bukti' => ['required','image','mimes:jpg,jpeg,png','max:2048'],
        ]);

        $laporan = Points::findOrFail($id);
        // simpan file ke disk `storage/app/public/laporan_bukti/...`
        $filename = null;
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $filename = time() . '_point.' . $file->getClientOriginalExtension();
            $file->storeAs('public/images', $filename);
        }
        $laporan->bukti = $filename;
        $laporan->save();

        return redirect()->back()->with('success', 'bukti berhasil diperbarui.');
    }

    public function histori()
    {
        // ambil nama user yang sedang login
        $nama = auth()->user()->name;

        // ambil hanya laporan yang dibuat oleh user tersebut, urut terbaru
        $histori = \App\Models\Points::where('warga', $nama)
                    ->orderBy('created_at', 'desc')
                    ->get();

        // reuse view 'table-point', tapi kirim data yg sudah difilter
        return view('histori', [
            'title'  => 'Histori Laporan',
            'points' => $histori,
        ]);
    }

    public function showdata($id)
    {
        // ambil nama user yang sedang login
        $nama = auth()->user()->name;

        // cari laporan dengan id yang dipilih dan milik user tersebut
        $point = \App\Models\Points::where('id', $id)
                    ->where('warga', $nama)
                    ->firstOrFail();

        // lempar ke view detail (resources/views/point/show.blade.php)
        return view('showdata', [
            'title' => 'Detail Laporan',
            'point' => $point,
        ]);
    }




}