<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UsersController extends Controller
{
    protected $usersModel;

    public function __construct()
    {
        $this->usersModel = new User();
    }

    public function index()
    {
        // Ambil semua poin dari model
        $user = $this->usersModel->users(); // asumsi ada method 

        $features = [];
        foreach ($user as $p) {
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id_user' => $p->id_user,
                    'nama' => $p->name,
                    'phone' => $p->phone,
                    'email' => $p->email,
                    'alamat' => $p->alamat,
                    'tanggal lahir' => $p->tanggal_lahir,
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id_user)
    {
        $user = $this->usersModel->user($id_user); 
        if (!$user) {
            return response()->json(['message' => 'Point not found'], 404);
            //bila user tidak ada dalam database
        }

        $features = [];
        foreach ((array) $user as $p) {
            $features[] = [
                'type' => 'Feature', //semua field di tabel user
                'properties' => [
                    'id_user' => $p->id_user,
                    'nama' => $p->name,
                    'phone' => $p->phone,
                    'email' => $p->email,
                    'alamat' => $p->alamat,
                    'tanggal lahir' => $p->tanggal_lahir,
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id_user)
    {
        $user = $this->usersModel->find($id_user);
        if (!$user) {
            return redirect()->back()->with('error', 'users tidak ditemukan');
        }


        $user->delete();

        return redirect()->back()->with('success', 'users berhasil dihapus');
    }

    public function table()
    {
        $user = User::where('role', 1)->get();
        return view('table-users', [
            'title' => 'Table Users',
            'user' => $user,
        ]);
    }

}
