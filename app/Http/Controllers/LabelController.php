<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrintedLabel;

class LabelController extends Controller
{
    public function index()
    {
        $labels = PrintedLabel::latest()->get();
        return view('labels.index', compact('labels'));
    }

    public function create()
    {
        $items = \App\Models\Item::orderBy('name_ar')->get(['name_ar', 'item_code']);
        $clients = \App\Models\Client::orderBy('company_name')->get(['company_name']);
        return view('labels.create', compact('items', 'clients'));
    }

    public function print(Request $request)
    {
        $data = $request->validate([
            'brand_name' => 'required|string',
            'item_name' => 'required|string',
            'manufacturer' => 'required|string',
            'manufacturer_address' => 'required|string',
            'exporter' => 'required|string',
            'exporter_address' => 'required|string',
            'importer' => 'required|string',
            'importer_address' => 'required|string',
            'production_date' => 'required|string',
            'expiry_date' => 'required|string',
            'batch_code' => 'required|string',
            'website' => 'required|string',
            'copies' => 'required|integer|min:1',
        ]);

        PrintedLabel::create($data);

        return view('labels.print', compact('data'));
    }
}
