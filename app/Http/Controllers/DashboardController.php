<?php

namespace App\Http\Controllers;
use App\Models\Points;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->points = new Points();

    }

    public function index()
    {
        $data = [
            "title" => "Pelaporan Desa",
            "total_points" => $this->points->count(),
        ];
            
        
        return view('dashboard', $data);
    }
}
